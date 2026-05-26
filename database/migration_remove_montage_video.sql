-- Migration : suppression complète de la compétence "Montage vidéo"
-- À exécuter en production pour supprimer la ligne de la table `competences`

-- 1. Supprimer les liaisons experts ↔ montage-video
DELETE ec FROM expert_competences ec
INNER JOIN competences c ON c.id = ec.competence_id
WHERE c.slug = 'montage-video';

-- 2. Supprimer la compétence elle-même
DELETE FROM competences WHERE slug = 'montage-video';
