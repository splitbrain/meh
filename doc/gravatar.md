# Gravatar Support in Meh

Meh integrates with [Gravatar](https://gravatar.com/) to display user avatars next to comments. This provides a familiar and consistent way for commenters to have their profile images appear across different websites.

## How It Works

When a comment is displayed:

1. Meh checks if the comment has a custom avatar URL (e.g., from Mastodon imports)
2. If not, it generates a Gravatar URL based on the commenter's email address or name
3. The Gravatar service returns either:
   - The user's Gravatar image if they have one registered
   - A fallback image based on your configuration

## Configuration

You can configure Gravatar behavior using these settings:

```
# Set the fallback image type
./meh config gravatar_fallback "initials"

# Set the content rating
./meh config gravatar_rating "g"
```

### Fallback Options

When a user doesn't have a Gravatar, you can choose what appears instead:

- `initials`: Generates an image with the user's initials (default)
- `mp`: Mystery Person silhouette
- `identicon`: Geometric pattern
- `monsterid`: Generated "monster" avatar (did you know those were [invented by Meh's creator](https://www.splitbrain.org/blog/2007-01/20_monsterid_as_gravatar_fallback)?)
- `wavatar`: Generated face
- `retro`: 8-bit style pixelated face
- `robohash`: Robot avatar
- `blank`: Transparent image
- Or any URL to a custom default image

### Content Rating

Control the maximum rating of Gravatar images:

- `g`: Suitable for all audiences
- `pg`: May contain rude gestures, provocative clothing, etc.
- `r`: May contain harsh language, violence, nudity, etc.
- `x`: May contain hardcore sexual imagery or extremely disturbing violence


## Privacy Considerations

- Email addresses are never exposed to site visitors
- Only the MD5 hash of the email is sent to Gravatar
- Users who don't want to use Gravatar can simply use an email address not registered with the service
