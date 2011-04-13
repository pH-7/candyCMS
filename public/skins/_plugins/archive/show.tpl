<section>
  <ul>
    {foreach from=$data item=d key=month}
      <li>
        <a href="#" class="js-archive_month">{$month} ({$d|@count})</a>
        <ul class="js-archive_entries">
        {foreach from=$d item=entry}
          <li>
            <a href="/blog/{$entry.id}/{$entry.encoded_title}" title="{$entry.date}">
              {$entry.title}
            </a>
          </li>
        {/foreach}
        </ul>
      </li>
    {/foreach}
  </ul>
</section>
{literal}
  <script type="text/javascript">
    var myAccordion = new Fx.Accordion($$('.js-archive_month'), $$('.js-archive_entries'), {
      display: -1,
      alwaysHide: true
    });
  </script>
{/literal}