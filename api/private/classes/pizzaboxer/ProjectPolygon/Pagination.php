<?php

namespace pizzaboxer\ProjectPolygon;

class Pagination
{
	// this is ugly and sucks
	// really this is only for the forums
	// everything else uses standard next and back pagination

	public static int $page = 1;
	public static int $pages = 1;
	public static string $url = '/';
	public static array $pager = [1 => 1, 2 => 1, 3 => 1];

	public static function initialize()
	{
		self::$pager[1] = self::$page-1; self::$pager[2] = self::$page; self::$pager[3] = self::$page+1;

		if(self::$page <= 2){ self::$pager[1] = self::$page; self::$pager[2] = self::$page+1; self::$pager[3] = self::$page+2; }
		if(self::$page == 1){ self::$pager[1] = self::$page+1; }

		if(self::$page >= self::$pages-1){ self::$pager[1] = self::$pages-3; self::$pager[2] = self::$pages-2; self::$pager[3] = self::$pages-1; }
		if(self::$page == self::$pages){ self::$pager[1] = self::$pages-1; self::$pager[2] = self::$pages-2; }
		if(self::$page == self::$pages-1){ self::$pager[1] = self::$pages-2; self::$pager[2] = self::$pages-1; }
	}

	public static function insert()
	{
		if(self::$pages <= 1) return;
	?>
<nav>
	<ul class="pagination justify-content-end mb-0">
	  	<li class="page-item<?=self::$page<=1?' disabled':''?>">
			<a class="page-link" <?=self::$page>1?'href="'.self::$url.(self::$page-1).'"':''?>aria-label="Previous">
				<span aria-hidden="true">&laquo;</span>
			    <span class="sr-only">Previous</span>
			</a>
		</li>
		<li class="page-item<?=self::$page==1?' active':''?>"><a class="page-link"<?=self::$page!=1?' href="'.self::$url.'1" ':''?>>1</a></li>
	    <?php if(self::$pages > 2){ if(self::$page > 3){ ?><li class="page-item disabled"><a class="page-link" href="#" tabindex="-1">&hellip;</a></li><?php } ?>
	    <?php for($i=1; $i<4; $i++){ if(self::$page == $i-1 || self::$pages-self::$page == $i-2) break; ?>
	    <li class="page-item<?=self::$page==self::$pager[$i]?' active':''?>"><a class="page-link"<?=self::$page!=self::$pager[$i]?' href="'.self::$url.self::$pager[$i].'" ':''?>><?=number_format(self::$pager[$i])?></a></li>
		<?php } ?>
	    <?php if(self::$page < self::$pages-2){ ?><li class="page-item disabled"><a class="page-link" href="#" tabindex="-1">&hellip;</a></li><?php } } ?>
	    <li class="page-item<?=self::$page==self::$pages?' active':''?>"><a class="page-link"<?=self::$page!=self::$pages?' href="'.self::$url.self::$pages.'" ':''?>><?=number_format(self::$pages)?></a></li>
	    <li class="page-item<?=self::$page>self::$pages?' disabled':''?>">
			<a class="page-link" <?=self::$page<self::$pages?'href="'.self::$url.(self::$page+1).'"':''?>aria-label="Next">
				<span aria-hidden="true">&raquo;</span>
			    <span class="sr-only">Previous</span>
			</a>
		</li>
  	</ul>
</nav>
	<?php
	}
}