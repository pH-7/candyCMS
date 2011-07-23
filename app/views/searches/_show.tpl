{foreach $tables as $table}
  <h2>{$table.title}</h2>
  <ol>
    {foreach $table as $data}
      {if $data.id > 0}
        <li>
          <a href="/{$table.section}/{$data.id}/highlight/{$search}">
            {$data.title}
          </a>,
          {$data.date}
        </li>
      {/if}
    {/foreach}
  </ol>
{/foreach}