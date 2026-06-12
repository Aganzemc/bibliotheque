-- Optional PostgreSQL helpers for reports and future PHP query migration.
-- Execute this file after 01_schema.sql. It can be executed before or after seeds.

create or replace view public.v_livres_avec_categories as
select
    l.*,
    string_agg(distinct c.nom, ', ' order by c.nom) as categories
from public.livres l
left join public.livre_categorie lc on l.id = lc.livre_id
left join public.categories c on lc.categorie_id = c.id
group by l.id;

create or replace view public.v_emprunts_en_cours as
select
    e.*,
    l.titre,
    l.isbn,
    m.nom,
    m.prenom,
    m.classe,
    (current_date - e.date_retour_prevue) as jours_retard
from public.emprunts e
join public.livres l on e.livre_id = l.id
join public.membres m on e.membre_id = m.id
where e.statut = 'en_cours'
order by e.date_retour_prevue asc;

create or replace view public.v_emprunts_archives as
select
    e.*,
    l.titre,
    l.isbn,
    m.nom,
    m.prenom,
    m.classe
from public.emprunts e
join public.livres l on e.livre_id = l.id
join public.membres m on e.membre_id = m.id
where e.statut = 'retourne'
order by e.date_retour_reelle desc;

create or replace view public.v_retards as
select
    e.id,
    m.nom,
    m.prenom,
    l.titre,
    e.date_retour_prevue
from public.emprunts e
join public.membres m on e.membre_id = m.id
join public.livres l on e.livre_id = l.id
where e.statut = 'en_cours'
  and e.date_retour_prevue < current_date;

create or replace view public.v_livres_quantite_invalide as
select id, titre, quantite_totale, quantite_disponible
from public.livres
where quantite_disponible > quantite_totale;

