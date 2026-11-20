# What Coolify deploys for anypress-demo-playground: the image GitHub Actions built for this commit (README.md).
ARG SOURCE_COMMIT
FROM ghcr.io/otaku-solutions/anypress-demo-playground:sha-${SOURCE_COMMIT}
