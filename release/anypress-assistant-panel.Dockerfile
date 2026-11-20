# What Coolify deploys for anypress-assistant-panel: the image GitHub Actions built for this commit (README.md).
ARG SOURCE_COMMIT
FROM ghcr.io/otaku-solutions/anypress-assistant-panel:sha-${SOURCE_COMMIT}
