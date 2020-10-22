INSERT INTO users (username, password)
  VALUES('alice@example.com', '$2y$10$zoVe6qqXL8TvkqzuDjLyM.TmBNilL0aVLRxjchjzR1SWfgUGQrqsa');
INSERT INTO users (username, password)
  VALUES('bob@example.com', '$2y$10$xNtPDEcCk7LIqWuxRjT7hOM4y0HcnArCUrSMX0gz1AJ9kfHLyaPL.');


INSERT INTO hw_credentials (
    user_id,
    public_key_credential_id,
    type,
    transports,
    attestation_type,
    trust_path,
    aaguid,
    credential_public_key,
    user_handle,
    counter)
VALUES(
    1,
    'bl08_a',
    'yes',
    'all',
    'direct',
    '=>',
    '000-0000-01',
    '---public-key---',
    'uh',
    1);
