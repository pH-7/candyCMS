<div id="newsletter">
  <form method='post' action='/newsletter'>
    {if $error_email}
      <div class="error">{$error_email}</div>
    {/if}
    <fieldset>
      <legend>{$lang_headline}</legend>
      <small>{$lang_description}</small>
      <div class="input">
        <input type="email" name='email' title='{$lang_email}' autofocus />
      </div>
      <div class="submit">
        <input type='submit' value='{$lang_headline}' />
      </div>
    </fieldset>
  </form>
</div>