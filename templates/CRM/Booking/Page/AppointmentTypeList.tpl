{* ch.ipik.booking — AppointmentTypeList.tpl *}
<div class="crm-container">
  <h3>{ts}Types de rendez-vous{/ts}</h3>

  <div style="margin-bottom:1rem;">
    <a href="{crmURL p='civicrm/booking/appointment-type/edit'}" class="button">{ts}+ Nouveau type{/ts}</a>
  </div>

  <table class="display dataTable" style="width:100%">
    <thead>
      <tr>
        <th>{ts}Couleur{/ts}</th>
        <th>{ts}Libellé{/ts}</th>
        <th>{ts}Durée{/ts}</th>
        <th>{ts}Attribution{/ts}</th>
        <th>{ts}Accès{/ts}</th>
        <th>{ts}Actif{/ts}</th>
        <th>{ts}Actions{/ts}</th>
      </tr>
    </thead>
    <tbody>
      {foreach from=$types item=t}
      <tr>
        <td><span style="display:inline-block;width:18px;height:18px;border-radius:50%;background:{$t.color};vertical-align:middle;"></span></td>
        <td><strong>{$t.label}</strong>{if $t.description}<br><small style="color:#6b7280">{$t.description}</small>{/if}</td>
        <td>{$t.duration_minutes} min</td>
        <td>{$selectorLabels[$t.therapist_selector]|default:$t.therapist_selector}</td>
        <td>
          {if $t.requires_existing_contact}<span class="crm-tag" title="{ts}Contact existant requis{/ts}">🔒 Existant</span>{/if}
          {if $t.requires_account_creation}<span class="crm-tag" title="{ts}Compte WP obligatoire{/ts}">👤 Compte WP</span>{/if}
          {if !$t.requires_existing_contact && !$t.requires_account_creation}<span style="color:#9ca3af">{ts}Public{/ts}</span>{/if}
        </td>
        <td>{if $t.is_active}✓{else}<span style="color:#9ca3af">—</span>{/if}</td>
        <td>
          <a href="{crmURL p='civicrm/booking/appointment-type/edit' q="id=`$t.id`"}" class="crm-hover-button">{ts}Modifier{/ts}</a>
        </td>
      </tr>
      {foreachelse}
      <tr><td colspan="7" style="text-align:center;"><em>{ts}Aucun type configuré. Créez-en un pour commencer.{/ts}</em></td></tr>
      {/foreach}
    </tbody>
  </table>
</div>
