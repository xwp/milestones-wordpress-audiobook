<?php
header( 'Content-Type: application/rss+xml; charset=utf8' );

if ( file_exists( 'manifest.json' ) ) {
	$podcast_items = json_decode( file_get_contents( 'manifest.json' ), true );
} else {
	die( 'Missing manifest.json' );
}

echo '<'.'?xml version="1.0" encoding="UTF-8" ?'.'>';
?>
<rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">
	<channel>
		<title>Milestones: The Story of WordPress</title>
		<description>The audio was generated via a script using OS X text-to-speech engine. See https://github.com/xwp/milestones-wordpress-audiobook</description>
		<link>https://github.com/WordPress/book</link>
		<itunes:image href="https://raw.githubusercontent.com/WordPress/book/master/Resources/illustrations/exports/Cover.png" />
		<copyright>Milestones: The Story of WordPress is licensed under Creative Commons and the GPL. Generated audio may be used according to Apple's terms of use.</copyright>
		<pubDate><?php echo gmdate( 'r' ) ?></pubDate>
		<ttl>1800</ttl>
		<?php foreach ( $podcast_items as $episode_number => $podcast_item ) : ?>
			<?php
			$url = 'http://' . $_SERVER['HTTP_HOST'] . dirname( $_SERVER['SCRIPT_NAME'] ) . '/' . $podcast_item['enclosure'];
			?>
			<item>
				<title><?php echo htmlspecialchars( $podcast_item['chapter_title'] ); ?></title>
				<link><?php echo htmlspecialchars( $url ); ?></link>
				<guid><?php echo htmlspecialchars( $url ); ?></guid>
				<enclosure url="<?php echo htmlspecialchars( $url, ENT_QUOTES ); ?>" type="audio/mpeg" />
				<pubDate><?php echo htmlspecialchars( @gmdate( 'r', strtotime( '2015-01-01' ) + $episode_number * 86400 ) ); ?></pubDate>
				<itunes:author>WordPress Contributors</itunes:author>
				<description><?php echo htmlspecialchars( $podcast_item['description'] ); ?></description>
			</item>
		<?php endforeach; ?>
	</channel>
</rss>
