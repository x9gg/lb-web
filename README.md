# lb-web

A simple webapp to display a basic info about the request to verify the CDN headers.

This app built using [Slim 4](https://www.slimframework.com/docs/v4/).

The deployed as a docker image, and it should include the following environment variables

- `APP_HEADER_TRACE_ID_NAME` is needed to display request trace id to debug the request further
- `APP_HEADER_CDN_VERIFICATION_API_KEY_NAME` and `APP_HEADER_CDN_VERIFICATION_API_SECRET_NAME`
  - our cluster verify most of the request that is coming correctly from the cdn with correct keys
  - this environment variable is needed to redact the values of these values
- `APP_ENV_SITE_TITLE`, page title
- `APP_ENV_SITE_COPYRIGHT_YEAR`
- `APP_ENV_SITE_COPYRIGHT_NAME` 
- `APP_ENV_SITE_COPYRIGHT_URL`

## running on dev
### requirements
- PHP 8.4
- composer
- roadrunner (to run the dev using `composer run:develop`)
- alternative: run using `php -S localhost:8080` from `src/public`

## production
### docker image [TBA]

### kubernetes [TBA]


## pipelines [TBA]

