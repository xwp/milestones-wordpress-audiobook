# Audiobook for _Milestones: The Story of WordPress_

This repo contains a [script](generate-audiobook.php) for generating an audiobook for _[Milestones: The Story of WordPress](https://github.com/WordPress/book)_.

The script will automatically download the latest [EPUB](https://github.com/WordPress/book/blob/master/Formats/Milestones-The-Story-of-WordPress.epub)
file from the WordPress book repo, and convert it to audio files using the text-to-speech (TTS) engine on OSX.

The duration of the generated TTS audio will be about 4 hours, depending on the speech rate you choose. Each
chapter gets a separate audio file.

## Generating Audio

```bash
./generate-audiobook.php
```

You may pass the `--voice` and `--rate` options accepted by the OS X say command, for example:

```bash
./generate-audiobook.php --voice Alex --rate 275
```

The script depends on
the `say` command in OS X. Audio files will be written to an `output` subdirectory.

To convert the AIFF audio files from the `say` command into MP3 files, `sox` is required (and must be installed with MP3 support). Without `sox`, only the AIFF files will be created.

To install sox using brew:
```
brew install sox --with-lame
```

If you already have sox installed but need to add MP3 support:
```
brew reinstall sox --with-lame
```

Files must be converted to MP3 before being used in podcasts.

Generated audio may be used according to Apple's terms of use (likely personal use only).

The output of this script can be used to create a podcast. Upload the contents of the `output` directory upon completion,
and then add the URL to the contained `podcast.php` file into your podcasting app. Again, this depends on MP3 conversion
via `sox`.

## Credits

Author: [Weston Ruter](https://weston.ruter.net/) ([@westonruter](https://twitter.com/westonruter)), [XWP](https://make.xwp.co).

Work derived from the [Listenability](https://wordpress.org/plugins/listenability/) project.

License: GPLv2+
