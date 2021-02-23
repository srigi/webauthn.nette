DROP TABLE IF EXISTS [users];
CREATE TABLE [users] (
    [id] INT IDENTITY(1,1),
    [username] NVARCHAR(255) NOT NULL,
    [password] NVARCHAR(255) NOT NULL,
    CONSTRAINT [PK__users__id] PRIMARY KEY ([id])
);
CREATE INDEX [users_username_index] ON [users] ([username]);


DROP TABLE IF EXISTS [hw_credentials];
CREATE TABLE [hw_credentials] (
    [id] INT IDENTITY(1,1),
    [user_id] INT NOT NULL,
    [public_key_credential_id] NVARCHAR (255) NOT NULL,
    [type] NVARCHAR (255) NOT NULL,
    [transports] NVARCHAR (1000) NOT NULL,
    [attestation_type] NVARCHAR (255) NOT NULL,
    [trust_path] NVARCHAR (1000) NOT NULL,
    [aaguid] NVARCHAR (255) NOT NULL,
    [credential_public_key] NVARCHAR (255) NOT NULL,
    [user_handle] NVARCHAR (255) NOT NULL,
    [counter] INT NOT NULL,
    CONSTRAINT [PK__hw_credentials__id] PRIMARY KEY ([id]),
    CONSTRAINT [hw_credentials_public_key_credential_id_unique] UNIQUE ([public_key_credential_id])
);
CREATE INDEX [hw_credentials_user_id_index] ON [hw_credentials]([user_id]);
ALTER TABLE [hw_credentials]
    ADD CONSTRAINT [FK__hw_credentials__user_id]
    FOREIGN KEY ([user_id])
    REFERENCES [users]([id])
        ON DELETE CASCADE
        ON UPDATE CASCADE;
