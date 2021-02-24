<div style="background-color:#A2C617;padding:20px;margin-left:-20px;bottom:0px;min-height:calc(100vh - 150px)">
    <h1>Spareparts.Live Layer</h1>
    
    <div style="font-size:1.2em">
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" >
            <br/><br/>
            <input type="hidden" name="action" value="spl_save_config"/>
            <label for="splToken">Token:</label>
            <input type="text" id="splToken" maxlength="21" name="token" value="<?php echo $this->token; ?>"/>
            <button type="submit" style="background-color:#212A72;color:#fff">Save</button>

            <br/><br/><br/><br/>
            <a href="https://spareparts.live" target="_blank" title="Open spareparts.live website in a new tab" style="color:#212A72;text-decoration:none"><span class="dashicons dashicons-arrow-right-alt"></span>&nbsp;To get your token, please register at Spareparts.Live</a><br/><br/>
            <a href="https://my.spareparts.live" target="_blank" title="Open my.spareparts.live in a new tab" style="color:#212A72;text-decoration:none"><span class="dashicons dashicons-arrow-right-alt"></span>&nbsp;Manage my (e)Catalogs</a><br/>
        
        </form>
    </div>
</div>


