<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<!--{{dom:head}}-->
	<?php echo \PHPFuse\Output\Dom\Document::dom("head")->execute(); ?>
</head>
<body>
	<nav>[NAV]</nav>
	<main>
		<?php echo $this->view()->get($args); ?>
	</main>
	<footer>[FOOTER]</footer>
</body>
</html>