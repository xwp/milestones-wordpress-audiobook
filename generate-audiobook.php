#!/usr/bin/env php
<?php
/**
 * Create Audiobook from "Milestones: The Story of WordPress"
 *
 * See readme.md
 */

chdir( __DIR__ );

if ( 'cli' !== php_sapi_name() ) {
	echo "Error: This script is to only be executed from the command line.\n";
	exit( 1 );
}

$args = getopt( '', array(
	'voice:',
	'rate:',
) );

system( 'command -v say > /dev/null', $exit_code );
if ( 0 !== $exit_code ) {
	echo "Error: Unable to locate say command. Are you on a Mac?\n";
	die( $exit_code );
}

system( 'command -v sox > /dev/null', $exit_code );
if ( 0 !== $exit_code ) {
	echo "Warning: Please install sox to create MP3s.\n";
}

$say_args = array(
	'rate' => 250,
);
foreach ( array( 'rate', 'voice' ) as $key ) {
	if ( ! empty( $args[ $key ] ) ) {
		$say_args[ $key ] = $args[ $key ];
	}
}

if ( ! file_exists( 'Milestones-The-Story-of-WordPress.epub' ) ) {
	system( 'wget -O Milestones-The-Story-of-WordPress.epub https://github.com/WordPress/book/blob/master/Formats/Milestones-The-Story-of-WordPress.epub?raw=true', $exit_code );
	if ( 0 !== $exit_code ) {
		exit( $exit_code );
	}
}
if ( ! file_exists( 'Milestones-The-Story-of-WordPress/' ) ) {
	system( 'unzip -d Milestones-The-Story-of-WordPress Milestones-The-Story-of-WordPress.epub', $exit_code );
	if ( 0 !== $exit_code ) {
		exit( $exit_code );
	}
}
chdir( 'Milestones-The-Story-of-WordPress/' );

$skipped_items = array(
	'OEBPS/title-page.html',
	'OEBPS/front-cover.html',
	'OEBPS/table-of-contents.html',
);

$toc_document = new DOMDocument();
$toc_document->load( 'book.opf' );
$toc_xpath = new DOMXPath( $toc_document );
$toc_xpath->registerNamespace( 'opf', 'http://www.idpf.org/2007/opf' );

$output_dir = __DIR__ . '/output';
if ( ! file_exists( $output_dir ) ) {
	mkdir( $output_dir );
}

$chapter_tts_text_contents = '';
$chapter_number = 0;

$podcast_items = array();
$mp3_failures = 0;

foreach ( $toc_xpath->query( '//opf:item[@media-type="application/xhtml+xml"]' ) as $item ) {
	/** @var DOMElement $item */
	if ( in_array( $item->getAttribute( 'href' ), $skipped_items ) ) {
		continue;
	}

	print "Reading: " . $item->getAttribute( 'href' ) . PHP_EOL;
	$item_document = new DOMDocument();
	$item_document->load( $item->getAttribute( 'href' ) );

	$item_xpath = new DOMXPath( $item_document );
	$item_xpath->registerNamespace( 'html', 'http://www.w3.org/1999/xhtml' );

	foreach( $item_xpath->query( '//*[ contains( @class, "wp-caption" ) ]' ) as $caption ) {
		$caption->parentNode->removeChild( $caption );
	}
	foreach( $item_xpath->query( '//html:h3[ @class = "front-matter-number" ]' ) as $front_matter_number ) {
		$front_matter_number->parentNode->removeChild( $front_matter_number );
	}
	foreach ( $item_xpath->query( '//html:h3[@class = "chapter-number"]' ) as $chapter_number_el ) {
		/** @var \DOMElement $chapter_number */
		$chapter_number_el->insertBefore( $item_document->createTextNode( 'Chapter ' ), $chapter_number_el->firstChild );
	}

	// Make sure each list item ends in a sentence termination marker.
	foreach ( $item_xpath->query( '//html:li' ) as $li ) {
		/** @var \DOMElement $li */
		if ( ! preg_match( '/[,;\.\?!]\s*$/', $li->textContent ) ) {
			$li->appendChild( $item_document->createTextNode( '.' ) );
		}
	}
	foreach ( $item_xpath->query( '//html:h1 | //html:h2 | //html:h3 | //html:h4 | //html:h5 | //html:h6' ) as $heading ) {
		/** @var \DOMElement $heading */
		$heading->insertBefore( $item_document->createTextNode( "\n\n[[slnc 200]]" ), $heading->firstChild );
		$heading->appendChild( $item_document->createTextNode( '[[slnc 200]]' ) );
	}
	foreach ( $item_xpath->query( '//html:p | //html:h1 | //html:h2 | //html:h3 | //html:h4 | //html:h5 | //html:h6 | //html:ul | //html:ol' ) as $p ) {
		/** @var \DOMElement $p */
		$p->insertBefore( $item_document->createTextNode( "\n\n" ), $p->firstChild );
		$p->appendChild( $item_document->createTextNode( "\n\n" ) );
	}
	foreach ( $item_xpath->query( '//html:ul/li' ) as $li ) {
		/** @var \DOMElement $li */
		// @todo Use something like this instead of asterisk? $bullet = html_entity_decode( '&bull;', ENT_QUOTES, get_bloginfo( 'charset' ) );
		$li->insertBefore( $item_document->createTextNode( ' - ' ), $li->firstChild );
		$li->appendChild( $item_document->createTextNode( "\n" ) );
	}
	foreach ( $item_xpath->query( '//html:ol' ) as $ol ) {
		/** @var \DOMElement $ol */
		$i = 0;
		foreach ( $item_xpath->query( './li', $ol ) as $li ) {
			/** @var \DOMElement $li */
			$i += 1;
			$li->insertBefore( $item_document->createTextNode( " $i. " ), $li->firstChild );
		}
	}
	foreach ( $item_xpath->query( '//html:br' ) as $br ) {
		/** @var \DOMElement $br */
		$br->parentNode->appendChild( $item_document->createTextNode( "\n" ) );
	}
	foreach ( $item_xpath->query( '//html:hr' ) as $hr ) {
		/** @var \DOMElement $hr */
		$hr->appendChild( $item_document->createTextNode( "\n\n[[slnc 1000]]---------------------\n\n" ) );
	}
	foreach ( $item_xpath->query( '//html:abbr[@title]' ) as $abbr ) {
		/** @var \DOMElement $abbr */
		$abbr->appendChild( $item_document->createTextNode( sprintf( ' (%s)', $abbr->getAttribute( 'title' ) ) ) );
	}
	foreach ( $item_xpath->query( '//html:strong | //html:em | //html:b | //html:i' ) as $el ) {
		/** @var \DOMElement $el */
		$el->insertBefore( $item_document->createTextNode( '[[emph +]]' ), $el->firstChild );
		$el->appendChild( $item_document->createTextNode( '[[emph -]]' ) );
	}
	foreach ( $item_xpath->query( '//html:blockquote' ) as $li ) {
		/** @var \DOMElement $li */
		$li->appendChild( $item_document->createTextNode( "\n" ) );
	}

	$element_names = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'ul', 'ol', 'blockquote', 'q', 'abbr', 'strong', 'em', 'i', 'b', 'hr' );
	$element_id = 0;
	foreach ( $item_xpath->query( '//' . join( ' | //', $element_names ) ) as $heading ) {
		/** @var \DOMElement $heading */
		$element_id += 1;
		$prefix = "[[cmnt tag:{$heading->nodeName}#$element_id]]";
		$heading->insertBefore( $item_document->createTextNode( $prefix ), $heading->firstChild );
		$suffix = "[[cmnt tag:/{$heading->nodeName}#$element_id]]";
		$heading->appendChild( $item_document->createTextNode( $suffix ) );
	}

	$text_content = $item_xpath->query( '//html:body' )->item( 0 )->textContent;

	$text_content = preg_replace( "/\n +/", "\n", $text_content );
	$text_content = preg_replace( "/\n\n\n+/", "\n\n", $text_content );
	$text_content = trim( $text_content );

	$chapter_tts_text_contents .= $text_content;

	if ( preg_match( '/copyright|part-\d\d\d/', $item->getAttribute( 'href' ) ) ) {
		continue;
	} else {
		$basename = basename( $item->getAttribute( 'href' ), '.html' );
		$input_txt_file = $output_dir . sprintf( '/%02d-%s.txt', $chapter_number, $basename );
		$output_aiff_file = $output_dir . sprintf( '/%02d-%s.aiff', $chapter_number, $basename );
		$output_mp3_file = $output_dir . sprintf( '/%02d-%s.mp3', $chapter_number, $basename );
		file_put_contents( $input_txt_file, $chapter_tts_text_contents );

		if ( file_exists( $output_mp3_file ) ) {
			echo "Skipping creating audio file since already exists: $output_mp3_file\n";
		} else {
			$options = array_merge(
				$say_args,
				array(
					'input-file' => $input_txt_file,
					'output-file' => $output_aiff_file,
				)
			);
			$cmd = 'say';
			foreach ( $options as $key => $value ) {
				$cmd .= sprintf( ' --%s=%s', $key, escapeshellarg( $value ) );
			}
			echo "Creating $basename audio via: $cmd\n";
			system( $cmd, $exit_code );
			if ( 0 !== $exit_code ) {
				echo "Error: Failed to generate audio.\n";
				exit( $exit_code );
			}

			$cmd = sprintf( 'sox %s %s', $output_aiff_file, $output_mp3_file );
			echo "Converting audio to MP3 via: $cmd\n";
			system( $cmd, $exit_code );
			if ( 0 !== $exit_code ) {
				echo "Error: Failed to convert to mp3 via SOX.\n";
				$mp3_failures += 1;
			} else {
				unlink( $output_aiff_file );
			}
		}

		$podcast_items[] = array(
			'chapter_title' => $item_xpath->query( '//html:title' )->item( 0 )->textContent,
			'input_txt_file' => $input_txt_file,
			'output_mp3_file' => $output_mp3_file,
		);

		$chapter_tts_text_contents = '';
		$chapter_html_contents = '';
		$chapter_number += 1;
	}
}

if ( 0 === $mp3_failures ) {
	$json_manifest = array();
	foreach ( $podcast_items as $podcast_item ) {
		$json_manifest[] = array(
			'chapter_title' => $podcast_item['chapter_title'],
			'description' => preg_replace( '/\[\[\w+.*?\]\]/', '', file_get_contents( $podcast_item['input_txt_file'] ) ),
			'enclosure' => basename( $podcast_item['output_mp3_file'] ),
		);
	}

	echo "Creating podcast data\n";
	file_put_contents( $output_dir . '/manifest.json', json_encode( $json_manifest, JSON_PRETTY_PRINT ) );

	chdir( $output_dir );
	if ( ! file_exists( 'podcast.php' ) ) {
		echo "Adding podcast.php symlink\n";
		symlink( '../podcast.php', 'podcast.php' );
	}
} else {
	echo "Notice: The MP3 files could not be generated, so the podcast will not be available.\n";
}