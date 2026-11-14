import { listEntries, openRemoteZip, readEntry, type ReadRange } from './zip.ts';

export type { ReadRange };

/** How much one range request fetches. A whole WordPress site's central directory is about this size. */
const BLOCK_BYTES = 4 * 1024 * 1024;

/**
 * Serves reads from whole blocks, each fetched once. yauzl reads the central directory a few bytes at a
 * time (two reads per file), which for a WordPress site is tens of thousands of requests and gets refused
 * by the bucket (HTTP 429); this makes it one or two.
 */
function blockReader(read: ReadRange, size: number, blockBytes = BLOCK_BYTES): ReadRange {
  const blocks = new Map<number, Promise<Buffer>>();
  const block = (i: number) => {
    let b = blocks.get(i);
    if (!b) {
      b = read(i * blockBytes, Math.min(size, (i + 1) * blockBytes) - 1);
      blocks.set(i, b);
    }
    return b;
  };
  return async (start, end) => {
    const parts: Buffer[] = [];
    for (let i = Math.floor(start / blockBytes); i <= Math.floor(end / blockBytes); i++) {
      const b = await block(i);
      const from = Math.max(start, i * blockBytes) - i * blockBytes;
      const to = Math.min(end, (i + 1) * blockBytes - 1) - i * blockBytes;
      parts.push(b.subarray(from, to + 1));
    }
    return Buffer.concat(parts);
  };
}

/**
 * One file from a zip read with range requests, without fetching the whole archive: yauzl reads the end
 * record and the central directory, then just that file, through whole blocks (blockReader). Null when the
 * zip has no such file.
 */
export async function readZipEntry(read: ReadRange, size: number, name: string, maxBytes: number): Promise<Buffer | null> {
  const zip = await openRemoteZip(blockReader(read, size), size).catch(() => {
    throw new Error('Not a zip');
  });
  try {
    const entry = (await listEntries(zip)).find((e) => e.fileName === name);
    if (!entry) return null;
    if (entry.compressedSize > maxBytes) throw new Error(`${name} is larger than ${maxBytes} bytes`);
    if (entry.compressionMethod !== 0 && entry.compressionMethod !== 8) throw new Error(`${name} uses an unsupported compression method`);
    return await readEntry(zip, entry, maxBytes);
  } finally {
    zip.close();
  }
}
