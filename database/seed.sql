-- Default admin user (password: changeme — MUST change on first login)
INSERT INTO admin_users (email, password_hash, name) VALUES (
    'kenny@bluewavecreativedesign.com',
    '$2y$10$PLACEHOLDER_HASH_REPLACE_ON_DEPLOY',
    'Kenny Siddons'
);

-- AT Wilmington as tenant #1
INSERT INTO churches (slug, name, timezone, pco_event_id) VALUES (
    'at-wilmington',
    'AT Wilmington',
    'America/New_York',
    '945124'
);
