/**
 * ch.ipik.booking — therapist-agenda.js
 * Interactions légères sur la page agenda thérapeute (backoffice CiviCRM).
 */
(function ($) {
  'use strict';

  $(function () {

    // Survol carte RDV — afficher le nom complet si tronqué
    $(document).on('mouseenter', '.ipik-appt-card', function () {
      var $card = $(this);
      var contact = $card.find('.ipik-appt-card__contact').text().trim();
      var type    = $card.find('.ipik-appt-card__type').text().trim();
      var time    = $card.find('.ipik-appt-card__time').text().trim();
      $card.attr('title', time + ' — ' + type + '\n' + contact);
    });

    // Confirmation actions (annulation / complétion) — déjà gérée via onclick inline
    // Ici on peut enrichir avec une modale si nécessaire en Phase 2

    // Highlight colonne "aujourd'hui"
    var today = new Date().toISOString().slice(0, 10);
    $('.ipik-day-col').each(function () {
      var date = $(this).data('date');
      if (date === today) {
        $(this).addClass('ipik-day-col--today');
      }
    });

    // Color picker inline pour les formulaires (si présent sur cette page)
    var $txt    = $('input[name="color"]');
    var $picker = $('#ipik-color-picker');
    if ($txt.length && $picker.length) {
      $picker.val($txt.val() || '#3b82f6');
      $picker.on('input', function () { $txt.val($picker.val()); });
      $txt.on('input', function () {
        if (/^#[0-9a-f]{6}$/i.test($txt.val())) {
          $picker.val($txt.val());
        }
      });
    }

  });

}(CRM.$));
