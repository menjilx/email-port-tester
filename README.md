# SMTP Port Connectivity Tester

A simple PHP script to test outbound connectivity to common SMTP ports (25, 465, 587) from your server. This is useful for verifying if your VPS provider (like Hetzner, AWS, DigitalOcean) is blocking email ports.

## Features

- **Multi-Host Testing**: Checks connectivity against reliable SMTP hosts:
  - Gmail (`smtp.gmail.com`)
  - Yahoo Mail (`smtp.mail.yahoo.com`)
  - Office 365 (`smtp.office365.com`)
  - SendGrid (`smtp.sendgrid.net`)
  - Mailgun (`smtp.mailgun.org`)
- **Port Coverage**: Tests standard SMTP ports:
  - **25**: Standard SMTP (often blocked by ISPs/Cloud Providers)
  - **465**: SMTPS (SSL)
  - **587**: Submission (STARTTLS)
- **Modern UI**: Outputs a responsive, dark-mode HTML dashboard with:
  - Summary cards showing success rates per port.
  - Detailed breakdown by host.
  - Latency metrics and server banners.

## Usage

1.  **Upload** the `port.php` file to your web server.
2.  **Navigate** to the file in your browser (e.g., `http://your-domain.com/port.php`).
3.  **View Results**: The script will run the tests and display the results immediately.

## Requirements

- PHP 7.4 or higher
- `stream_socket_client` function enabled (usually enabled by default)
- Outbound internet access from the server

## Troubleshooting

- **All Failures**: If all ports fail, check your server's firewall (UFW, iptables) or security groups.
- **Port 25 Fails**: Many cloud providers block port 25 by default to prevent spam. You may need to request unblocking or use port 587/465.
