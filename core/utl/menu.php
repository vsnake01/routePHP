<?php
/**
 * Description of menu
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */
class Menu {
	
	public static function tabs($links)
	{
		$menu = '<ul class="nav nav-tabs">';
		
		foreach ($links as $link=>$text) {
			$menu .= 
				'<li' .
				((\Url::getPath() == $link) ? ' class="active"' : '' ) .
				'><a href="' . $link . '">' . $text . '</a>' .
				'</li>';
		}
		$menu .= '</ul>';
		
		return $menu;
	}
}
