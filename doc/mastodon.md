## Mastodon Integration

Meh includes built-in support for integrating with Mastodon, allowing you to import replies to your Mastodon posts as comments on your site.

### Configuration

To set up Mastodon integration:

1. Configure your Mastodon account in your site settings:

```
./meh config mastodon_account "@yourusername@instance.social"
```

2. If you're using GoToSocial, even read access requires an API token. The easiest way to get one is to use the [Access Token Generator for Mastodon API](https://takahashim.github.io/mastodon-access-token/). You only need the `read` scope.

```
./meh config mastodon_token "your-api-token"
```

3. Set up a cron job to periodically fetch new posts and replies:

```
# Run every hour to check for new Mastodon posts and replies
0 * * * * /path/to/meh mastodon
```

For multi-site setups you need to set up a `mastodon_account` config and cron job for each site. But you can have all sites use the same account.

For setting up the cronjob when using Docker, you can either use the host's cron or use a container like [Ofelia](https://github.com/mcuadros/ofelia) or [Chadburn](https://github.com/PremoWeb/chadburn).

### How It Works

The Mastodon integration:

1. Fetches posts from your configured Mastodon account (uses the Mastodon API)
2. Identifies posts that link to your site
3. Tracks these posts in the database
4. Periodically checks for replies to these posts
5. Imports replies as comments on the corresponding blog post

This creates a seamless bridge between discussions on your blog and on the Fediverse.

### Manual Import

You can manually trigger the Mastodon import process at any time:

```
./meh mastodon
```

This is useful for testing or for an initial import of existing conversations.
