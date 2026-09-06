{* ch.ipik.booking — TherapistList.tpl *}
<div class="crm-container">
  <h3>{ts}Intervenant·es{/ts}</h3>

  <div style="margin-bottom:1rem;">
    <a href="{crmURL p='civicrm/booking/therapist/edit'}" class="button">{ts}+ Nouvel·le intervenant·e{/ts}</a>
  </div>

  <table class="display dataTable" style="width:100%">
    <thead>
      <tr>
        <th>{ts}Couleur{/ts}</th>
        <th>{ts}Nom{/ts}</th>
        <th>{ts}WP User ID{/ts}</th>
        <th>{ts}Buffer{/ts}</th>
        <th>{ts}Horizon{/ts}</th>
        <th>{ts}Actif{/ts}</th>
        <th>{ts}Actions{/ts}</th>
      </tr>
    </thead>
    <tbody>
      {foreach from=$therapists item=t}
      <tr>
        <td><span style="display:inline-block;width:18px;height:18px;border-radius:50%;background:{$t.color};vertical-align:middle;"></span></td>
        <td>
          <a href="{crmURL p='civicrm/contact/view' q="cid=`$t.contact_id`"}">{$t.display_name}</a>
        </td>
        <td>{$t.wp_user_id|default:'—'}</td>
        <td>{$t.buffer_minutes} min</td>
        <td>{$t.max_advance_days} j</td>
        <td>{if $t.is_active}✓{else}<span style="color:#9ca3af">—</span>{/if}</td>
        <td>
          <a href="{crmURL p='civicrm/booking/therapist/edit' q="id=`$t.id`"}" class="crm-hover-button">{ts}Modifier{/ts}</a>
          <a href="{crmURL p='civicrm/booking/therapist-agenda' q="cid=`$t.contact_id`"}" class="crm-hover-button">{ts}Agenda{/ts}</a>
        </td>
      </tr>
      {foreachelse}
      <tr><td colspan="7" style="text-align:center;"><em>{ts}Aucun·e intervenant·e enregistré·e.{/ts}</em></td></tr>
      {/foreach}
    </tbody>
  </table>
</div>
