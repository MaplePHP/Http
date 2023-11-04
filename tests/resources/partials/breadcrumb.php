
<article>
    <header>
        <h2><?php echo $obj->name; ?></h2>
        <h6><?php echo $obj->date("DateTime")->format("Y/m/d"); ?></h6>
        <p><?php echo $obj->content("Str")->excerpt(20)->get(); ?></p>
    </header>
    
    <?php if ($obj->feed()->count() > 0) : ?>
    <ul>
        <?php foreach ($obj->feed()->fetch()->get() as $row) : ?>
        <li>
            <strong><?php echo $row->headline("Str")->ucfirst()->get(); ?></strong><br>
            <?php echo $row->description; ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</article>