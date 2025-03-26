# Email Notifications for New Comments

Meh can send email notifications when new comments are posted on your site. This feature helps you stay informed about new activity without needing to constantly check your site.

Next to the comment and author info, the notification mail will also let you know about the comment's status, so you know if you still need to approve it.

## Configuration

To enable email notifications, you need to configure the following settings:

1. Set the email address where you want to receive notifications:
```
./meh config notify_email your@email.com
```

2. Configure your SMTP server settings:
```
./meh config smtp_host smtp.example.com
./meh config smtp_port 587
./meh config smtp_encryption tls  # Options: tls, ssl, or empty for no encryption
./meh config smtp_user your_username  # If authentication is required
./meh config smtp_pass your_password  # If authentication is required
./meh config smtp_verify 0  # To disable SSL certificate verification
```

Of course, you can configure these settings per site by using the `--site` parameter. Or you can use environment variables to set these values globally.

