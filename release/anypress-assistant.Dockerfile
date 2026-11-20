# What Coolify deploys for anypress-assistant: the image GitHub Actions built for this commit (README.md).
ARG SOURCE_COMMIT
FROM ghcr.io/otaku-solutions/anypress-assistant:sha-${SOURCE_COMMIT}
