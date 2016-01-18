# Audiobook for _Milestones: The Story of WordPress_

This repo contains a [script](generate-audiobook.php) for generating an audiobook for _[Milestones: The Story of WordPress](https://github.com/WordPress/book)_.

The duration of the generated TTS audio will be about 4 hours, depending on the speech rate you choose. Each
chapter gets a separate audio file.

The script uses the text-to-speech (TTS) engine on OS X to create the audio files. It depends on
the `say` command on OS X. Audio files will be written to an `output` subdirectory.
To create a podcast, it also requires `sox` to be installed (with MP3 support) to convert the AIFF
audio files from the `say` command into MP3 files; without this, only the AIFF files will be created.

The script will automatically download the latest [EPUB](https://github.com/WordPress/book/blob/master/Formats/Milestones-The-Story-of-WordPress.epub)
file from the WordPress book repo.

You may pass the `--voice` and `--rate` options accepted by the OS X say command,
for example:

```bash
./generate-audiobook.php --voice Alex --rate 275
```

Generated audio may be used according to Apple's terms of use (likely personal use only).

The output of this script can be used to create a podcast. Upload the contents of the `output` directory upon completion,
and then add the URL to the contained `podcast.php` file into your podcasting app. Again, this depends on MP3 conversion
via `sox`.

## Credits

Author: Weston Ruter, XWP

Work was derived from the [Listenability](https://wordpress.org/plugins/listenability/) project.

License: GPLv2+