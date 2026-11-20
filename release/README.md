# Release images

The four apps built from this repository are built on GitHub, never on the server that runs them. When an app's files
change on `main`, its workflow (`.github/workflows/image.yml`, started by the app's own workflow) builds the image,
publishes it to GHCR as `ghcr.io/otaku-solutions/<app>:sha-<commit>` and `:latest`, then moves the branch
`release/<app>` to that commit.

| App | Image and branch | Workflow | Built from |
| --- | --- | --- | --- |
| The API (api.anypress.io) | `anypress-api` | `api-image.yml` | `Dockerfile` |
| The website assistant's server | `anypress-assistant` | `assistant-image.yml` | `agent/Dockerfile` |
| The website assistant's chat panel | `anypress-assistant-panel` | `panel-image.yml` | `agent/panel/Dockerfile` |
| WordPress Playground (demo.anypress.site) | `anypress-demo-playground` | `playground-image.yml` | `playground/Dockerfile` |

The server deploys each app from its `release/<app>` branch with `release/<app>.Dockerfile`, which only names the image
published for that commit (`SOURCE_COMMIT`), so a deploy downloads the image and builds nothing. The branch moves only
after the image is published, so a deploy never asks for an image that is missing.

The packages are public, like the repository, so pulling them needs no login. GitHub creates a new package as private:
the first time an image is published, set its visibility to public in the package's settings on GitHub.
