# Comment Moderation

Meh includes a simple comment moderation system to prevent spam and inappropriate content.

## Comment Status Workflow

Comments in Meh can have one of the following statuses:

- **Pending**: New comments awaiting review (default for most comments)
- **Approved**: Comments that have been approved and are publicly visible
- **Spam**: Comments marked as spam
- **Deleted**: Comments that have been removed

## Automatic Status and Flood Control

Meh will automatically approve comments based on certain conditions:

1. **Admin Comments**: Comments submitted by authenticated administrators are automatically approved
2. **Trusted Users**: Comments from users who have previously had a comment approved will be automatically approved

On the other hand, comments from users who have had a comment marked as spam will be automatically marked as spam.

To not be overwhelmed by comments. Only one comment can be pending per IP address at a time.

> Meh uses a user token stored in localstorage to identify returning users. This is of course not useful to prevent spam, so IP addresses are used as fallback identification.


## Administrator Moderation

Administrators can change a comment's status at any time:

1. **Login**: Use the [meh-login](../frontend/src/components/meh-login/readme.md) component to authenticate as an admin
2. **Moderation Actions**: Once logged in, the comments list will display moderation controls:
   - **Approve**: Make a comment publicly visible
   - **Reject**: Move a comment back to pending status
   - **Mark as Spam**: Flag a comment as spam
   - **Delete**: Mark a comment as removed
