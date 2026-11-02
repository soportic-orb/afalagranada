-- Inscripcions a activitats en una web externa
ALTER TABLE activities ADD COLUMN external_registration TINYINT(1) NOT NULL DEFAULT 0 AFTER ask_course;
ALTER TABLE activities ADD COLUMN external_url VARCHAR(500) NULL AFTER external_registration
