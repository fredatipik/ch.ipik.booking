{* ch.ipik.booking — Form/Availability.tpl *}
<div class="crm-container crm-form-block">
  <p class="description">
    {ts}Horaires de consultation, semaine type. Deux plages possibles par jour (matin et après-midi). Laissez vide pour retirer une plage.{/ts}
  </p>

  <table class="ipik-avail">
    <thead>
      <tr>
        <th>{ts}Jour{/ts}</th>
        <th colspan="2">{ts}Plage 1{/ts}</th>
        <th colspan="2">{ts}Plage 2{/ts}</th>
      </tr>
    </thead>
    <tbody>
      {foreach from=$dayRows item=row}
        {assign var="dow" value=$row.dow}
        {assign var="s1"  value="start_`$dow`_1"}
        {assign var="e1"  value="end_`$dow`_1"}
        {assign var="s2"  value="start_`$dow`_2"}
        {assign var="e2"  value="end_`$dow`_2"}
        <tr>
          <th scope="row">{$row.label}</th>
          <td>{$form.$s1.html}</td>
          <td>{$form.$e1.html}</td>
          <td>{$form.$s2.html}</td>
          <td>{$form.$e2.html}</td>
        </tr>
      {/foreach}
    </tbody>
  </table>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
    <a href="{$cancelURL}" class="button cancel">{ts}Annuler{/ts}</a>
  </div>
</div>

<style>
.ipik-avail { border-collapse:collapse; margin:1rem 0; }
.ipik-avail th, .ipik-avail td { padding:.35rem .6rem; text-align:left; }
.ipik-avail thead th { font-size:.8rem; color:#6b7280; border-bottom:1px solid #e5e7eb; }
.ipik-avail tbody th { font-weight:600; width:7rem; }
.ipik-avail tbody tr:nth-child(even) { background:#fafafa; }
.ipik-avail input { width:5.5rem; }
</style>

{literal}
<script>
(function () {
  // Les champs horaires sont rendus en <input type="text"> par CiviCRM ;
  // on les bascule en type="time" pour obtenir le sélecteur natif.
  document.querySelectorAll('.ipik-avail input[type="text"]').forEach(function (el) {
    el.type = 'time';
    el.step = 300;
  });
})();
</script>
{/literal}
