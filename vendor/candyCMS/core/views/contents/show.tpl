{strip}
  {foreach $contents as $c}
    <article class='contents'>
      <header class='page-header'>
        <h1>
          {$c.title}
          {if $_SESSION.user.role >= 3}
            <a href='{$c.url_update}'>
              <img src='{$_PATH.images}/candy.global/spacer.png'
                  class='icon-update js-tooltip'
                  alt='{$lang.global.update.update}'
                  title='{$lang.global.update.update}'
                  width='16' height='16' />
            </a>
          {/if}
        </h1>
        <p>
          {$lang.global.last_update}:
          &nbsp;
          <time datetime='{$c.datetime_w3c}'>
            {$c.datetime}
          </time>
          &nbsp;
          {$lang.global.by}
          &nbsp;
          <a href='{$c.author.url}' rel='author'>{$c.author.full_name}</a>
        </p>
      </header>
      {if $c.teaser}
        <p class='summary'>{$c.teaser}</p>
      {/if}
      {$c.content}
      <footer>
        {if $_REQUEST.id && (!isset($_REQUEST.action) || $_REQUEST.action !== 'page')}
          <hr />
          <div id='socialshareprivacy'></div>
          <script src='{$_PATH.js}/core/jquery.socialshareprivacy{$_SYSTEM.compress_files_suffix}.js' type='text/javascript'></script>
        {/if}
      </footer>
    </article>
    <script src='{$_PATH.js}/core/jquery.fancybox{$_SYSTEM.compress_files_suffix}.js' type='text/javascript'></script>
    <script src='{$_PATH.js}/core/jquery.capty{$_SYSTEM.compress_files_suffix}.js' type='text/javascript'></script>
    <script type="text/javascript">
      $(document).ready(function(){
        $(".js-fancybox").fancybox();
        $('.js-image').capty({ height: 35 });

        if($('#socialshareprivacy').length > 0){
          $('#socialshareprivacy').socialSharePrivacy({
            services : {
              facebook : {
                'language' : '{$WEBSITE_LOCALE}',
                'dummy_img' : '{$_PATH.images}/jquery.socialshareprivacy/dummy_facebook.png'
              },
              twitter : {
                'dummy_img' : '{$_PATH.images}/jquery.socialshareprivacy/dummy_twitter.png'
              },
              gplus : {
                'dummy_img' : '{$_PATH.images}/jquery.socialshareprivacy/dummy_gplus.png',
                'display_name' : 'Google Plus'
              }
            },
            css_path : ''
          });
        };
      });

      $('.js-media').each(function(e) {
        var $this = $(this);
        $.getJSON(this.title, function(data) {
          $this.html(data['html']);
        });
      });
    </script>
  {/foreach}
{/strip}