-- Initial data for the Bibliotheque CPS Supabase database.
-- Execute this file after 01_schema.sql.
--
-- Default accounts:
--   admin@cps.com / admin123
--   biblio@cps.com / admin123
--
-- Passwords are generated with PostgreSQL pgcrypto bcrypt and are compatible
-- with PHP password_verify().

insert into public.users (nom, email, password, role, statut)
values
    ('Administrateur', 'admin@cps.com', crypt('admin123', gen_salt('bf', 10)), 'admin', 'actif'),
    ('Bibliothecaire', 'biblio@cps.com', crypt('admin123', gen_salt('bf', 10)), 'bibliothecaire', 'actif')
on conflict (email) do update set
    nom = excluded.nom,
    password = excluded.password,
    role = excluded.role,
    statut = excluded.statut;

insert into public.parametres (id, duree_emprunt_jours, max_livres_par_membre, amende_par_jour)
values (1, 15, 3, 5000)
on conflict (id) do update set
    duree_emprunt_jours = excluded.duree_emprunt_jours,
    max_livres_par_membre = excluded.max_livres_par_membre,
    amende_par_jour = excluded.amende_par_jour;

insert into public.categories (nom, description)
select v.nom, v.description
from (
    values
    ('Roman', 'Livres de fiction romanesque'),
    ('Science', 'Sciences et technologies'),
    ('Histoire', 'Livres historiques'),
    ('BD', 'Bandes dessinees')
) as v(nom, description)
where not exists (
    select 1
    from public.categories c
    where c.nom = v.nom
);

insert into public.livres (
    titre,
    auteur,
    isbn,
    numero_etagere,
    date_edition,
    quantite_totale,
    quantite_disponible
)
values
    ('Les Miserables', 'Victor Hugo', '978-2253096337', 'A12', '1862-01-01', 3, 3),
    ('1984', 'George Orwell', '978-2070368228', 'B05', '1949-06-08', 2, 2),
    ('Sapiens', 'Yuval Noah Harari', '978-2226257017', 'C08', '2011-01-01', 1, 1),
    ('Le Petit Prince', 'Antoine de Saint-Exupery', '978-2070612758', 'A03', '1943-04-06', 5, 5),
    ('Germinal', 'Emile Zola', '978-2253004226', 'A12', '1885-01-01', 2, 2)
on conflict (isbn) do update set
    titre = excluded.titre,
    auteur = excluded.auteur,
    numero_etagere = excluded.numero_etagere,
    date_edition = excluded.date_edition,
    quantite_totale = excluded.quantite_totale,
    quantite_disponible = excluded.quantite_disponible;

insert into public.membres (nom, prenom, classe, type)
select v.nom, v.prenom, v.classe, v.type::membre_type
from (
    values
    ('DIALLO', 'Mamadou', '6eme A', 'eleve'),
    ('KONE', 'Aminata', '4eme B', 'eleve'),
    ('TOURE', 'Ibrahim', '3eme C', 'eleve'),
    ('OUATTARA', 'Fatou', 'Terminale D', 'eleve'),
    ('SISSOKO', 'Drissa', 'Professeur', 'enseignant')
) as v(nom, prenom, classe, type)
where not exists (
    select 1
    from public.membres m
    where m.nom = v.nom
      and coalesce(m.prenom, '') = coalesce(v.prenom, '')
      and m.classe = v.classe
);

select setval(pg_get_serial_sequence('public.users', 'id'), coalesce((select max(id) from public.users), 1), true);
select setval(pg_get_serial_sequence('public.categories', 'id'), coalesce((select max(id) from public.categories), 1), true);
select setval(pg_get_serial_sequence('public.livres', 'id'), coalesce((select max(id) from public.livres), 1), true);
select setval(pg_get_serial_sequence('public.membres', 'id'), coalesce((select max(id) from public.membres), 1), true);
select setval(pg_get_serial_sequence('public.parametres', 'id'), coalesce((select max(id) from public.parametres), 1), true);
