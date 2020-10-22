DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    username TEXT NOT NULL,
    password TEXT NOT NULL
);
CREATE INDEX users_username ON users(username);


DROP TABLE IF EXISTS hw_credentials;
CREATE TABLE hw_credentials (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL,
    public_key_credential_id TEXT NOT NULL UNIQUE,
    type TEXT NOT NULL,
    transports TEXT NOT NULL,
    attestation_type TEXT NOT NULL,
    trust_path TEXT NOT NULL,
    aaguid TEXT NOT NULL,
    credential_public_key TEXT NOT NULL,
    user_handle TEXT NOT NULL,
    counter INTEGER NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
CREATE INDEX hw_credentials_user ON hw_credentials(user_id);
CREATE INDEX hw_credentials_public_key_credential_id ON hw_credentials(public_key_credential_id);
