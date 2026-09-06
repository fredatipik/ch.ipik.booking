{* ch.ipik.booking — Page/LocationList.tpl *}
<div class="crm-container">
  <p class="description">
    {ts}Chaque local dispose de son propre agenda. Lorsqu'un rendez-vous s'y tient, le créneau devient indisponible pour tous les intervenant·es, et l'agenda du local permet aussi de bloquer des plages sans passer par une réservation.{/ts}
  </p>

  <div style="margin:1rem 0">
    <a href="{crmURL p='civicrm/booking/location/edit' q='reset=1'}" class="button">{ts}+ Nouveau local{/ts}</a>
  </div>

  <table class="display dataTable" style="width:100%">
    <thead>
      <tr>
        <th></th>
        <th>{ts}Nom{/ts}</th>
        <th>{ts}Adresse{/ts}</th>
        <th>{ts}Agenda{/ts}</th>
        <th>{ts}Actif{/ts}</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      {foreach from=$locations item=loc}
      {assign var="loc_id" value=$loc.id}
      <tr>
        <td><span class="ipik-swatch" style="background:{$loc.color}"></span></td>
        <td><strong>{$loc.name}</strong></td>
        <td>{$loc.address|default:'—'}</td>
        <td>
          {if $loc.calendar_url}
            <span style="color:#065f46">{ts}Configuré{/ts}</span>
          {else}
            <span style="color:#9ca3af">{ts}Aucun{/ts}</span>
          {/if}
        </td>
        <td>{if $loc.is_active}✓{else}<span style="color:#9ca3af">—</span>{/if}</td>
        <td>
          <a href="{crmURL p='civicrm/booking/location/edit' q="id=`$loc_id`&reset=1"}"
             class="crm-hover-button">{ts}Modifier{/ts}</a>
        </td>
      </tr>
      {foreachelse}
      <tr>
        <td colspan="6" style="text-align:center;color:#9ca3af">
          <em>{ts}Aucun local. Sans local configuré, les rendez-vous ne bloquent que l'agenda de l'intervenant·e.{/ts}</em>
        </td>
      </tr>
      {/foreach}
    </tbody>
  </table>
</div>

<style>
.ipik-swatch { display:inline-block; width:18px; height:18px; border-radius:4px; vertical-align:middle; }
</style>
