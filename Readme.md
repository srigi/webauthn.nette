Nette Webauthn
==============

This is a minimal, yet complete example project demonstrating **[webauthn](https://developer.mozilla.org/en-US/docs/Web/API/Web_Authentication_API)** sign-in process in Nette framework.

During the sign-in phase, webapp checks if user have any HW credential info (public key) stored in the database. If yes, sign-in will forward request to another Presenter which will initiate a second step of authentication (2FA), utilizing verification via HW authenticator (Yubikey or similar).

![Sign-in screen with HW authenticator prompt](https://i.postimg.cc/65rRq1Sd/sign-in.png)

Requirements
------------

- local PHP 7.4 installation with `ext-json`, `ext-gmp`, `ext-sodium` and `pdo_sqlsrv`
- Docker & `docker-compose` (cli command)
- `composer`
- `openssl`
- HW authenticator to play with 🙂


Installation
------------

- clone project to your local machine
- copy `.env [example]` into `.env`, maybe adjust some values
- copy `config/local.neon [example]` into `config/local.neon`, maybe adjust some values

**note:** parameters `PHP_IDE_CONFIG` and `parameters.rpID` respectively represents the *origin* of running application (part of URL address you use to browse the web-application). See the [detailed diagram](https://nodejs.org/api/url.html#url_url_strings_and_url_objects) to understand what *origin* exactly is.

Setup them to origin you wish to use for access the web-application on your local machine. Values of these two parameters must not include port!

- setup localhost record for hostname equal to `parameters.rpID` in your `/etc/hosts` or local DNS:

  ```
  127.0.0.1  nette-webauthn.test
  ```

- create sqlite3 database and fill in tables & data:

  ```bash
  sqlite3 .data/main.sqlite3 < migrations/001_schema.sqlite3.sql
  sqlite3 .data/main.sqlite3 < migrations/002_fixtures.sqlite3.sql
  ```

### SSL
Javascript APIs for FIDO2 and webauthn are accessible only if web-application is served over HTTPS! So even for local development it is required to setup proper SSL certificates for the web-application. We will generate self-signed SSL cert for development server:

First let's create all needed crypto files for our *Certification authority*:

- generate CA's secret key:

  ```bash
  openssl genrsa \
      -out .ssl/ca.key 2048
  ```

- copy `.ssl/ca.conf [example]` into `.ssl/ca.conf`, maybe adjust some values

- generate CA's root certificate:

  ```bash
  openssl req -x509 -new -days 3650 -nodes -sha256 \
      -config .ssl/ca.conf \
      -key .ssl/ca.key \
      -out .ssl/ca.crt
  ```

- convert a certificate to DER format (only for Windows):

  ```bash
  openssl x509 -outform DER \
      -in .ssl/ca.crt \
      -out .ssl/ca.der
  ```

Now we can use this CA to sing & create a server SSL certificate(s). However, you will get the infamous security warning about an untrusted server. We must import CA's certificate into operating system and mark it as trusted.

In most cases, just double-click the `ca.crt` (or `ca.der` in case on Windows) to import the CA certificate into OS. Then mark it as trusted (macOS example):

![Setting the CA's certificate to be trusted by OS](https://i.postimg.cc/sDchrHv1/trusting.png)

Now let's create SSL certificate & key for *delelopment server*:

- generate `dhparam`:

  ```bash
  openssl dhparam \
      -out .ssl/dhparam 2048
  ```

- generate server's secret key:

  ```bash
  openssl genrsa \
      -out .ssl/server.key 2048
  ```

- copy `.ssl/server.csr.conf [example]` into `.ssl/server.csr.conf`, maybe adjust some values

- generate server's [csr](https://www.sslshopper.com/what-is-a-csr-certificate-signing-request.html):

  ```bash
  openssl req -new \
      -config .ssl/server.csr.conf \
      -key .ssl/server.key \
      -out .ssl/server.csr
  ```

- copy `.ssl/server.crt.conf [example]` into `.ssl/server.crt.conf`, maybe adjust some values

- generate server cert:

  ```bash
  openssl x509 -req -days 825 -sha256 \
      -CA .ssl/ca.crt \
      -CAkey .ssl/ca.key \
      -CAcreateserial \
      -CAserial .ssl/ca.seq \
      -extfile .ssl/server.crt.conf \
      -in .ssl/server.csr \
      -out .ssl/server.crt
  ```

Now you finished creating a self-signed SSL certificate for your development server. It is time to...

- build docker images:

  ```bash
  docker-compose build
  ```


Development
-----------

- start the web-application:

  ```bash
  docker-compose up
  ```

Visit `https://nette-webauthn.test:8000` (preconfigured origin) in your browser to see the welcome page.


Usage: Web authentication
-------------------------

Fist you must sign-in into your profile. In development database there are two user accounts pre-created in the database for you:

1. username: `alice@example.com`, password: `secret`
1. username: `bob@example.com`, password: `secret`

When in your profile, you can see the list of stored HW credentials:
![Profile page showing stored HW credentials](https://i.postimg.cc/V6D861wJ/profile.png)

Clicking the button *Add HW credential* you start so-called process of **attestation** - creating the asymmetric keypair on the HW authenticator and storing the public part in the database.

When application detects tha there are HW credentials store for the user, it will initiate **assertion** during sign-in process to enforce 2FA.
