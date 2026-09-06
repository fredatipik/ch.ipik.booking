# Booking IPIK

Extension CiviCRM de prise de rendez-vous, conçue pour un cabinet réunissant
plusieurs intervenant·es qui se partagent un ou plusieurs locaux.

Les patients réservent depuis un formulaire WordPress, les rendez-vous
s'inscrivent dans CiviCRM et se synchronisent avec les agendas CalDAV.

## Ce que fait l'extension

**Réservation en ligne** — Un shortcode WordPress affiche un formulaire en
quatre étapes : type de rendez-vous, créneau, coordonnées, confirmation. Le
créneau proposé tient compte des disponibilités déclarées, des rendez-vous
déjà pris, des agendas personnels et de l'occupation des locaux.

L'intervenant·e n'est jamais choisi·e par le patient : le système l'attribue
selon une stratégie configurable — rotation équitable, charge la plus faible,
ou tirage au sort.

**Deux façons de déclarer ses disponibilités.** Le mode *horaires
hebdomadaires* convient aux semaines régulières, avec congés et créneaux
exceptionnels en complément. Le mode *jours de travail déclarés* s'adresse
aux équipes irrégulières : un calendrier de six mois où chaque journée
travaillée se coche d'un clic, avec ses horaires et son local. Le mode se
règle globalement, et chaque intervenant·e peut adopter l'autre.

**Locaux.** Un local possède son propre agenda. Un rendez-vous qui s'y tient
le rend indisponible pour tous, et l'agenda du local permet réciproquement de
bloquer des plages sans passer par une réservation. Les événements qui y sont
déposés ne portent que le nom de l'intervenant·e.

**Synchronisation CalDAV.** Les rendez-vous alimentent l'agenda personnel de
l'intervenant·e et celui du local. Les événements qui s'y trouvent déjà
bloquent les créneaux correspondants. La synchronisation ne conditionne
jamais l'enregistrement : si un agenda est injoignable, le rendez-vous est
créé et l'incident consigné.

**Backoffice.** Création de rendez-vous au téléphone, avec choix parmi les
créneaux libres ou saisie libre pour les urgences. Liste filtrable de tous
les rendez-vous, agenda par intervenant·e, onglet « Rendez-vous » sur la
fiche patient, et un dashlet des prochains rendez-vous.

**Facturation.** Depuis un rendez-vous effectué, deux boutons mènent au
formulaire de contribution CiviCRM ou à celui de facture QR suisse, contact
pré-rempli. Le type financier et le statut par défaut se règlent une fois
pour toutes.

**E-mails.** Confirmation au patient et à l'intervenant·e, rappel avant le
rendez-vous, notification d'annulation aux deux parties. Les contenus sont
des modèles CiviCRM modifiables, avec des tokens propres à l'extension en
plus des tokens standards.

## Confidentialité

Un rendez-vous chez un thérapeute est une information médicale. Quelques
garde-fous sont posés par défaut : le dashlet ne montre que les rendez-vous
de l'intervenant·e connecté·e, jamais ceux des autres, y compris pour un
compte administrateur. Le sujet des activités CiviCRM ne porte que le type
de rendez-vous, jamais le nom du patient. Les agendas de locaux, partagés
entre plusieurs personnes, ne reçoivent que le nom de l'intervenant·e.

La création d'activités peut être désactivée si la seule existence d'un
rendez-vous ne doit pas apparaître dans les calendriers partagés.

## Prérequis

- CiviCRM 5.60 ou plus récent, testé sur 6.15
- WordPress 6.0 ou plus récent
- PHP 8.1 ou plus récent
- Extension cURL pour la synchronisation CalDAV

Facultatif : [`ch.ipik.swissQRinvoice`](https://github.com/fredatipik/ch.ipik.swissQRinvoice)
pour la facturation QR suisse, et `ch.ipik.smtprouter` pour router les envois
selon l'adresse d'expédition.

## Installation

```bash
cd [civicrm]/ext/
tar xzf ch.ipik.booking-x.y.z.tar.gz
cv ext:enable ch.ipik.booking
```

Puis **Administration → Extensions → Booking IPIK → Installer**.

La configuration complète est décrite dans [INSTALL.md](INSTALL.md) :
locaux, intervenant·es, disponibilités, types de rendez-vous, agendas CalDAV,
facturation, formulaire public, modèles d'e-mail.

## Licence

AGPL-3.0. Voir [LICENSE](LICENSE).
