<?php namespace Axisubs\Providers;


use Herbert\Framework\Application;
use Illuminate\Support\ServiceProvider;
use Twig_Environment;
use Twig_Loader_Filesystem;
use Twig_Extension_Debug;
use Twig_SimpleFunction;

/**
 * @see http://getherbert.com
 */
class TwigExtensionServiceProvider extends ServiceProvider
{

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
	}

	public function boot()
	{

		if ( isset( $this->app['twig'] ) )
		{
			$twig = $this->app['twig'];

			$twig->addFunction( new \Twig_SimpleFunction( '_e', function ( $text, $domain = 'default' )
			{
				return _e( $text, $domain );
			} ) );

			$twig->addFunction( new \Twig_SimpleFunction( '__', function ( $text, $domain = 'default' )
			{
				return __( $text, $domain );
			} ) );
			$twig->addFunction( new \Twig_SimpleFunction( 'translate', function ( $text, $domain = 'default' )
			{
				return translate( $text, $domain );
			} ) );

			$twig->addFunction( new \Twig_SimpleFunction( 'esc_html__', function ( $text, $domain = 'default' )
			{
				return esc_html__( $text, $domain );
			} ) );

			$twig->addFunction( new \Twig_SimpleFunction( 'esc_html_e', function ( $text, $domain = 'default' )
			{
				return esc_html_e( $text, $domain );
			} ) );

			/* actions and filters */
			$twig->addFunction( new \Twig_SimpleFunction( 'action', function ( $context )
			{
				$args = func_get_args();
				array_shift( $args );
				$args[] = $context;
				call_user_func_array( 'do_action', $args );
			}, array( 'needs_context' => true ) ) );

			$twig->addFilter( new \Twig_SimpleFilter( 'apply_filters', function ()
			{
				$args = func_get_args();
				$tag  = current( array_splice( $args, 1, 1 ) );

				return apply_filters_ref_array( $tag, $args );
			} ) );

			$twig->addFunction( new \Twig_SimpleFunction( 'function', array( &$this, 'exec_function' ) ) );

		}

	}

}
