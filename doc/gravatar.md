# Avatar Support in Meh

By default, Meh integrates with [Gravatar](https://gravatar.com/) to display user avatars next to comments. This provides a familiar and consistent way for commenters to have their profile images appear across different websites. However, Meh can also generate avatars locally without sending any data to Gravatar.

## How It Works

1. Meh will always prefer a custom avatar URL if provided (like from Mastodon imports).
2. If no custom avatar is available, or it fails to load, Meh will either generate a local avatar or use Gravatar.
3. If Gravatar is used and the user has no Gravatar image, a fallback image is displayed which again can be local or from Gravatar.

## Configuration

First decide whether you want to use Gravatar or local avatars. You can set this in the configuration:

```bash
# Use Gravatar
./meh config avatar gravatar

# Use locally generated ring avatars
./meh config avatar ring
```

See below for available local avatar types.

When using Gravatar, you can also set the default avatar type and content rating. This is done in the same way as other configuration options:

```
# Set the fallback image type
./meh config gravatar_fallback monsterid

# Set the content rating
./meh config gravatar_rating "g"
```

See below for available avatar types.

### Avatar Types

The following avatar types can be used as either a fallback Gravatar or a locally generated avatar.

| Avatar Type   | Local       | Gravatar Fallback | Description                                                                                                                                                  |
|---------------|-------------|-------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `mp`          | Yes         | Yes               | Mystery Person silhouette                                                                                                                                    |
| `ring`        | Yes         | Yes               | Three ring segments forming a unique pattern                                                                                                                 |
| `multiavatar` | Yes         | Yes               | Colorful multicultural avatar. See [multiavatar.com](https://multiavatar.com/)                                                                               |
| `identicon`   | Yes         | Yes               | Geometric pattern (default gravatar fallback)                                                                                                                |
| `monsterid`   | No          | Yes               | Generated "monster" avatar (did you know those were [invented by Meh's creator](https://www.splitbrain.org/blog/2007-01/20_monsterid_as_gravatar_fallback)?) |
| `wavatar`     | No          | Yes               | Generated face                                                                                                                                               |
| `retro`       | No          | Yes               | 8-bit style pixelated face                                                                                                                                   |
| `robohash`    | No          | Yes               | Robot avatar                                                                                                                                                 |
| `blank`       | Yes         | Yes               | Transparent image                                                                                                                                            |
| Custom URL    | No          | Yes               | Any URL to a custom default image                                                                                                                            |

Please note that the `initials` setting is no longer available to streamline the avatar generation process.

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
