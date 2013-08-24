<?php
/**
 * Description of menu
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */
class Menu {
	
	public static function tabs($links, $class=null, $header=null)
	{
		$class = $class ? $class : 'nav-tabs';
		$menu = '<ul class="nav '.$class.'">';
		
		if ($header) {
			$menu .= '<li class="nav-header">'.$header.'</li>';
		}
		
		foreach ($links as $link=>$text) {
			$menu .= 
				'<li' .
				((stristr(\Url::getPath(), $link) !== false) ? ' class="active"' : '' ) .
				'><a href="' . $link . '">' . $text . '</a>' .
				'</li>';
		}
		$menu .= '</ul>';
		
		return $menu;
	}
}
