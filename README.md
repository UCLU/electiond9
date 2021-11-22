# DONE

- Basic entity types for Election, Post, Candidate

# INSTALLATION

You must set up a key called "election" under /admin/config/system/keys

# Changes from Drupal 7

- This module now encrypts ballots by default, preventing nefarious access to anything connecting the user to the vote. They are only unencrypted for counting purposes.

# IN PROGRESS

# TODO

- Delete posts when election is deleted
- Delete candidates when post is deleted
- Customise "express interest", e.g. "proposal"
