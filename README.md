# lb-web

A simple whoami service that displays basic request information to verify CDN headers. 

Built using [Slim Framework 4](https://www.slimframework.com/), this application helps debug and validate incoming requests, particularly focusing on CDN header verification.

## Why "lb-web"?

The name "lb-web" (short for "load balancer web") reflects the service's primary use case in load balancer environments. This whoami service is designed to:

- Verify that CDN headers are being correctly passed through load balancers
- Debug request routing and header modifications
- Confirm load balancer configurations are working as expected
- Trace requests through the system using trace IDs
- Validate that traffic is being properly distributed in your infrastructure

It serves as a diagnostic tool when deployed behind load balancers, helping teams verify their routing, header management, and CDN configurations are functioning correctly.

## Features

- Displays detailed request information
- CDN header verification
- Request tracing support
- Configurable site information
- Docker-ready deployment

## Configuration

### Environment Variables

The following environment variables are not mandatory but are recommended for optimal functionality and customization:

| Variable | Description |
|----------|-------------|
| `APP_HEADER_TRACE_ID_NAME` | Header name for request trace ID |
| `APP_HEADER_CDN_VERIFICATION_API_KEY_NAME` | Header name for CDN API key verification |
| `APP_HEADER_CDN_VERIFICATION_API_SECRET_NAME` | Header name for CDN API secret verification |
| `APP_ENV_SITE_TITLE` | Website title |
| `APP_ENV_SITE_COPYRIGHT_YEAR` | Copyright year |
| `APP_ENV_SITE_COPYRIGHT_NAME` | Copyright holder name |
| `APP_ENV_SITE_COPYRIGHT_URL` | Copyright holder URL |
| `APP_VERSION` | Application version (set by GitHub Actions) |

## Development Setup

### Requirements

- PHP 8.4 or higher
- Composer
- RoadRunner (for development server)

### Local Development

1. Install dependencies:
```bash
composer install
```

2. Start the development server using RoadRunner:
```bash
composer run:develop
```

Alternative method using PHP's built-in server:
```bash
cd src/public
php -S localhost:8080
```

## Production Deployment

### Docker

The application is containerized and built using a multi-stage Docker build process [Dockerfile ](./Dockerfile)


Run the container:
```bash
docker run -p 8080:8080 \
  -e APP_HEADER_TRACE_ID_NAME=X-Trace-ID \
  -e APP_HEADER_CDN_VERIFICATION_API_KEY_NAME=X-CDN-Key \
  -e APP_HEADER_CDN_VERIFICATION_API_SECRET_NAME=X-CDN-Secret \
  -e APP_ENV_SITE_TITLE="LB Web" \
  -e APP_ENV_SITE_COPYRIGHT_YEAR="2024" \
  -e APP_ENV_SITE_COPYRIGHT_NAME="Your Company" \
  -e APP_ENV_SITE_COPYRIGHT_URL="https://example.com" \
  lb-web:latest
```

### Kubernetes [TBA]

Kubernetes deployment configuration will be added soon.

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Disclaimer and License

This software is provided "AS IS", without warranty of any kind, express or implied. By using lb-web, you acknowledge and agree:

- This tool is for testing and verification purposes only
- No guarantees are made about its suitability, reliability, or security for any purpose
- The authors and contributors will not be liable for any damages or issues arising from its use
- You are responsible for properly configuring and securing any deployment of this software

Use at your own risk. Review the [LICENSE](LICENSE) file for full terms.


## Support

For support, please open an issue in the GitHub repository.