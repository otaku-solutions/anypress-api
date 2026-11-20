# What Coolify deploys for anypress-api: the image GitHub Actions built for this commit (README.md).
ARG SOURCE_COMMIT
FROM ghcr.io/otaku-solutions/anypress-api:sha-${SOURCE_COMMIT}
