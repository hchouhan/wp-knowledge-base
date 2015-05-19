<?php

namespace WPKB;

class CodeHighlighting {

	/**
	 * @var Plugin
	 */
	protected $wpkb;

	/**
	 * @param $plugin
	 */
	public function __construct( \WPKB\Plugin $wpkb ) {
		$this->wpkb = $wpkb;
	}

	/**
	 * Add necessary hooks
	 */
	public function add_hooks() {

		// register shortcode
		add_shortcode( 'wpkb_code', array( $this, 'shortcode' ) );

		// lazy add actions
		add_action( 'template_redirect', array( $this, 'lazy_add' ) );
	}

	/**
	 * Performs a set of action, but only for `wpkb-article` posts.
	 *
	 * - Registers scripts and styles
	 * - Registers filters and action hooks to properly format code snippets
	 * - Prints inline JS in footer to initialize the Highlighter
	 *
	 * @return bool
	 */
	public function lazy_add() {

		if( ! is_singular( 'wpkb-article' ) ) {
			return false;
		}

		// get post that's being viewed
		$post = get_post();

		if( ! has_shortcode( $post->post_content, 'wpkb_code' ) ) {
			return false;
		}

		// add filters
		remove_filter( 'the_content', 'wpautop' );
		remove_filter('the_content', 'wptexturize');
		add_filter( 'the_content', 'wpautop' , 99);
		add_filter( 'the_content', 'shortcode_unautop',100 );

		// register scripts and styles
		add_action( 'wp_enqueue_scripts', array( $this, 'load_assets' ) );
		add_action( 'wp_footer', array( $this, 'print_inline_js' ), 99 );
		return true;
	}

	/**
	 * Load script & styles required for WP Docs search
	 */
	public function load_assets() {

		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_style( 'wpkb-code-highlighting', $this->wpkb->url( '/assets/css/code-highlighting' . $min . '.css' ) );
		wp_register_script( 'wpkb-code-highlighting', '//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.4/highlight.min.js', array( ), false, true );

		wp_enqueue_style( 'wpkb-code-highlighting' );
		wp_enqueue_script( 'wpkb-code-highlighting' );
	}

	/**
	 * @param        $args
	 * @param string $content
	 *
	 * @return string
	 */
	public function shortcode( $args, $content = '' ) {

		$defaults = array(
			'lang' => 'html'
		);
		$args = shortcode_atts( $defaults, $args, 'wpkb_code' );

		$content = trim( $content );
		$content = ltrim( $content, '\n' );
		$content = rtrim( $content, '\n' );

		$output = '<pre><code class="'. esc_attr( $args['lang'] ) .'">';
		$output .= esc_html( $content );
		$output .= '</code></pre>';

		return $output;
	}

	/**
	 * Print inline JS to initialize Highlight.js
	 */
	public function print_inline_js() {
		?>
		<script type="text/javascript">
			hljs.initHighlightingOnLoad();
		</script>
		<?php
	}


}