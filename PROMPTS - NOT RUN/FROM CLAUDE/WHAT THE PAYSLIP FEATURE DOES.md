now, a new payroll feature. build a new feature to the payroll module (check if it already exists first) that performs in the following manner:

add to payroll module and design it as a **Payslip Distribution & Secure Email Delivery** feature, integrated directly with the Pay Run.

### Recommended workflow

```text
PAY RUN
   ↓
Payroll calculation completed
   ↓
Payroll reviewed/approved
   ↓
Pay Run finalized
   ↓
Payroll Personnel clicks
"Send Payslips"
   ↓
System validates employees
   ↓
Generate individual PDF payslips
   ↓
Password-protect each PDF
   ↓
Retrieve employee's pre-set payslip password
   ↓
Email payslip to employee
   ↓
Record delivery status
   ↓
Audit Trail
```

## 1. Add "Send Payslips" to the Pay Run

After a pay run is successfully completed, the payroll personnel should see something like:

**Pay Run: August 2026**

```text
Status: FINALIZED

Employees:             246
Gross Payroll:         MK XXX
Net Payroll:           MK XXX

Payslips
────────────────────────────────────
Generated:             246 / 246
Emails Sent:           0 / 246
Failed:                0
Pending:               246

[ Generate Payslips ]
[ Preview Distribution ]
[ Send Payslips ]
[ View Delivery Status ]
```

The **Send Payslips** button should only become available after the pay run has been finalized/approved.

---

## 2. Employee profile — Payslip security settings

Add a section to each employee profile:

### **Payslip Delivery & Security**

**Email Address**

`employee@example.com`

**Payslip PDF Password**

`••••••••••`

**Confirm Password**

`••••••••••`

**Email Payslip**

`Enabled`

The password should be stored **securely**, not as plain text.

Ideally:

* Encrypt it at rest if the application needs to retrieve it for PDF generation.
* Never display the actual password to payroll personnel.
* Allow the employee to change it.
* Require confirmation when changing it.
* Record when it was last changed.

Because the system needs the password to encrypt the PDF, a normal one-way password hash alone would not be sufficient; the application needs a securely recoverable/encrypted value.

---

## 3. Generate a separate PDF for every employee

Don't create one large payroll PDF containing everyone's payslips.

Generate:

```text
Employee A → Payslip_August_2026.pdf
Employee B → Payslip_August_2026.pdf
Employee C → Payslip_August_2026.pdf
```

Each PDF should contain **only that employee's information**.

This is important for confidentiality.

---

## 4. Password-protect every PDF

Before sending:

```text
Payslip PDF
     ↓
Employee-specific password
     ↓
Encrypted PDF
     ↓
Email attachment
```

For example:

> Employee's configured payslip password → PDF password

The password should **not be included in the email**.

The employee already knows their pre-set password.

---

## 5. Email content

The email can be standardized.

**Subject:**

> Your Payslip — August 2026

**Body:**

> Dear [Employee Name],
>
> Your payslip for August 2026 is attached to this email.
>
> For your security, the attached PDF is password-protected. Please use your pre-set payslip password to open the document.
>
> If you have forgotten your payslip password, please contact the Payroll/HR department.
>
> Regards,
> Payroll Department

The system should **never send the PDF password in the same email**.

---

# 6. Distribution screen

I would create a dedicated **Payslip Distribution** page.

### Summary

```text
AUGUST 2026 PAYSLIP DISTRIBUTION

Total Employees       246
Ready to Send         242
Missing Email           2
Missing Password        1
Invalid Email           1
Sent                  238
Failed                  4
```

Then a table:

| Employee    | Email     | Password   | PDF   | Status        | Action |
| ----------- | --------- | ---------- | ----- | ------------- | ------ |
| John Banda  | john@...  | Configured | Ready | Sent          | View   |
| Mary Phiri  | mary@...  | Configured | Ready | Pending       | Send   |
| Peter Zulu  | —         | Configured | Ready | Missing Email | Fix    |
| Grace Mbewe | grace@... | Not Set    | Ready | Blocked       | Fix    |

**Never display the actual password.**

Instead show:

> Configured
> Not configured

---

# 7. Pre-send validation

Before the payroll officer can send the payslips, the system should check:

### Employee

* Active/included in pay run
* Valid email address
* Payslip password configured
* Payslip generated successfully
* PDF encryption successful

If any requirement fails, don't send that employee's payslip.

For example:

> **12 payslips cannot be sent because employee email addresses or payslip passwords are missing.**

The system should provide a list of affected employees.

---

# 8. Bulk sending

The payroll officer should be able to click:

> **Send All Payslips**

But I would also provide:

> **Send Selected**

This allows payroll personnel to resend a payslip to a particular employee without resending everyone else's.

---

# 9. Delivery status

Track each email independently.

Possible statuses:

```text
Pending
Generating
Generated
Queued
Sending
Sent
Delivered
Failed
Bounced
Cancelled
```

If the mail server provides delivery/bounce information, capture that too.

---

# 10. Resend functionality

Suppose an employee says:

> "I accidentally deleted my payslip."

Payroll personnel should be able to find:

**August 2026 → John Banda → Payslip**

and click:

> **Resend Payslip**

The system should generate/send the protected PDF again.

The action should be recorded in the audit trail.

---

# 11. Don't regenerate the entire payroll

This is important.

If someone needs a payslip resent, the system should **not rerun payroll**.

Instead:

```text
Finalized Pay Run
       ↓
Existing Payroll Result
       ↓
Generate/Load Payslip
       ↓
Encrypt PDF
       ↓
Send
```

The payslip should be based on the finalized payroll record.

---

# 12. Audit trail

Every distribution event should be logged.

For example:

```text
Payslip Distribution Audit

Employee: John Banda
Pay Run: August 2026
Action: Sent
Recipient: john@example.com
Performed By: Payroll Officer
Date: 31 Aug 2026 14:32
Status: Successful
```

Also record:

* Initial send
* Resend
* Failed delivery
* Email change
* Password change
* PDF generation failure
* Encryption failure

**Never log the actual PDF password.**

---

# 13. Security controls

Because payslips contain sensitive financial information, I would also add:

* Role-based access
* Restrict payslip distribution to authorized payroll personnel
* Audit trail
* Encrypted password storage
* Secure PDF encryption
* HTTPS for all application access
* No password in email body
* No password in application logs
* No password in audit logs
* No exposure of other employees' payslips
* Automatic access control based on employee/pay run
* Optional email delivery confirmation
* Optional employee notification when a payslip is resent

---

# 14. Employee portal

If your accounting system has an employee portal, I'd also add:

**My Payslips**

```text
August 2026     Available
July 2026       Available
June 2026       Available
May 2026        Available
```

But even when employees download payslips from the portal, you can still keep the PDFs password-protected.

---

# 15. Recommended database structure

I would avoid putting everything directly into the payroll table.

Create dedicated structures such as:

```text
employees
    ↓
employee_payslip_settings
    ↓
pay_runs
    ↓
payroll_results
    ↓
payslips
    ↓
payslip_distributions
    ↓
payslip_distribution_logs
```

### `employee_payslip_settings`

Could contain:

* employee_id
* email
* encrypted_payslip_password
* email_enabled
* password_last_changed_at
* updated_by
* timestamps

### `payslips`

Could contain:

* pay_run_id
* employee_id
* payslip_number
* period
* file_path/reference
* generated_at
* encryption_status
* generated_by

### `payslip_distributions`

Could contain:

* payslip_id
* employee_id
* email
* status
* sent_at
* delivered_at
* failed_at
* failure_reason
* attempt_count
* last_attempt_at

---

## One improvement I strongly recommend

Don't make the **payroll officer manually generate 200+ PDFs and email them individually**.

Give them a single workflow:

> **Finalize Pay Run → Generate Payslips → Validate → Review Distribution → Send Payslips**

with a progress indicator such as:

```text
Generating Payslips       ████████████████████ 100%
Encrypting PDFs           ████████████████████ 100%
Preparing Emails          ████████████████████ 100%
Sending                   ████████████░░░░░░░░  62%

152 / 246 sent
```

This will make the feature feel like a proper **enterprise payroll distribution system**, rather than simply an email button attached to payroll.

**One important technical point:** because the system must retrieve each employee's pre-set password to encrypt the PDF, do **not** use a normal irreversible password hash for this particular field. Store the payslip password using strong application-level encryption with the encryption key kept separately from the database.

add a **Payslip Portal** as part of the employee self-service area, while keeping the **same password-protected PDF requirement**.

The important point is that **portal access and PDF protection should be treated as two separate security layers**.

### Recommended structure

```text
EMPLOYEE SELF-SERVICE
│
├── Dashboard
├── My Profile
├── My Payslips
│   ├── Payslip History
│   ├── View Payslip
│   └── Download Payslip
├── My Leave
└── Notifications
```

### Payslip portal

Employees should see a page such as:

| Pay Period  | Payslip             | Status    | Action          |
| ----------- | ------------------- | --------- | --------------- |
| August 2026 | August 2026 Payslip | Available | View / Download |
| July 2026   | July 2026 Payslip   | Available | View / Download |
| June 2026   | June 2026 Payslip   | Available | View / Download |

The employee should **only ever see their own payslips**.

---

## Download workflow

When an employee clicks **Download**:

```text
Employee Login
      ↓
My Payslips
      ↓
Select Payslip
      ↓
System retrieves finalized payslip
      ↓
Generate/retrieve PDF
      ↓
Apply employee's payslip password
      ↓
Password-protected PDF
      ↓
Download
```

So even if someone somehow obtains the downloaded PDF, they still need the employee's **pre-set payslip password** to open it.

---

## Don't remove the password protection for portal downloads

I recommend that the system **always enforce PDF password protection**, regardless of whether the payslip was:

* Emailed
* Downloaded from the portal
* Re-downloaded
* Resent by payroll
* Generated again

There should be no "Download unprotected PDF" option for normal users.

---

## Employee Payslip Settings

In the employee profile:

### Payslip Security

```text
Payslip Email
    employee@example.com

Payslip Password
    ●●●●●●●●

Confirm Password
    ●●●●●●●●

Email Payslips
    ON

Portal Payslips
    ON
```

The employee can change their password through a secure process.

Payroll administrators should see:

> **Password configured**

rather than the actual password.

---

## Payslip portal dashboard

I'd make it more professional than simply a document list.

### My Payslips

**Latest Payslip**

> **August 2026**
> Net Pay: **MWK XXX,XXX**
> Pay Date: 31 August 2026
>
> **[ View Payslip ] [ Download PDF ]**

Then:

### Payslip History

Search/filter by:

* Year
* Month
* Pay period
* Payroll status

---

## View Payslip

When the employee clicks **View**, I recommend showing a **secure HTML preview** rather than automatically opening the encrypted PDF.

For example:

```text
PAYSLIP
────────────────────────────────────

Employee:       John Banda
Employee No:    EMP-00125
Department:     Finance
Pay Period:     August 2026

EARNINGS
Basic Salary              XXX,XXX
Housing Allowance          XX,XXX
Transport Allowance        XX,XXX

DEDUCTIONS
PAYE                       XX,XXX
Pension                    XX,XXX
Other                      XX,XXX

NET PAY                   XXX,XXX

[ Download Password-Protected PDF ]
```

The downloaded PDF remains encrypted.

---

## Email + portal integration

The final architecture becomes:

```text
                         FINALIZED PAY RUN
                                │
                                ↓
                         PAYSLIP GENERATION
                                │
                 ┌──────────────┴──────────────┐
                 ↓                             ↓
          EMAIL DISTRIBUTION             PAYSLIP PORTAL
                 │                             │
                 ↓                             ↓
      Password-Protected PDF         Password-Protected PDF
                 │                             │
                 ↓                             ↓
              Employee                    Employee
```

This gives employees **two ways to receive their payslip** while maintaining the same security standard.

---

## Additional features I recommend

### 1. Payslip availability

Don't make a payslip available in the portal until the pay run is finalized.

```text
Draft Pay Run
     ↓
Processing
     ↓
Reviewed
     ↓
Approved
     ↓
FINALIZED
     ↓
Payslip becomes available
```

### 2. Automatic notification

When the payslip becomes available:

> **Your August 2026 payslip is now available in the employee portal.**

Send this through:

* In-app notification
* Email
* Optional SMS/push notification

### 3. Download history

Record:

* Employee
* Payslip
* Download date/time
* IP/device information where appropriate
* Action

For example:

```text
Payslip: August 2026
Downloaded: 31 Aug 2026 14:25
User: John Banda
```

### 4. Revoke access

If an employee leaves the organization, their portal account can be disabled while historical payroll records remain preserved.

### 5. Password reset

If an employee forgets the payslip PDF password:

> **Forgot Payslip Password?**

The system should provide a secure password-reset process rather than exposing the old password to HR/payroll staff.

### 6. Multiple companies

Since you're building an accounting system, if your system eventually supports multiple companies, ensure payslip access is isolated by:

**Company → Employee → Pay Run → Payslip**

An employee from Company A must never be able to access Company B's payslips.

---

### Final recommended feature set

Your Payroll module would therefore have:

**Payroll Processing**
→ Pay Run
→ Approval/Finalization
→ Payslip Generation
→ **Payslip Email Distribution**
→ **Secure Payslip Portal**
→ **Password-Protected PDF Downloads**
→ Delivery/Download Tracking
→ Audit Trail

This gives you a much more complete **Employee Payslip Distribution & Self-Service system**, while preserving the password protection.

for security and storage efficiency:

The system should generate payslip PDFs **on demand or in memory**, encrypt them with the employee's pre-set payslip password, deliver/download them, and then discard the generated PDF. There should be **no permanent payslip PDF files stored on the server**.

### Recommended architecture

```text
                  FINALIZED PAY RUN
                         │
                         ↓
                 PAYROLL DATABASE
                         │
                         ↓
                 Payslip Data
                         │
          ┌──────────────┴──────────────┐
          ↓                             ↓
    EMAIL REQUEST                 PORTAL DOWNLOAD
          │                             │
          └──────────────┬──────────────┘
                         ↓
                  GENERATE PDF
                    IN MEMORY
                         ↓
                APPLY PDF PASSWORD
                         ↓
             ┌───────────┴───────────┐
             ↓                       ↓
       EMAIL ATTACHMENT          DOWNLOAD
             │                       │
             └───────────┬───────────┘
                         ↓
                  DISCARD PDF
                         ↓
                  NO PDF STORED
```

## What should be stored?

The **payroll data**, not the generated PDF.

For example, the database retains:

* Employee
* Pay run
* Pay period
* Basic salary
* Allowances
* Deductions
* PAYE
* Other statutory deductions
* Net pay
* Payroll transaction details
* Payslip reference/number
* Finalization status

Then the payslip can always be reconstructed from the finalized payroll data.

---

## Email process

When payroll personnel clicks:

> **Send Payslips**

the system should:

1. Retrieve finalized payroll information.
2. Generate an individual payslip PDF **in memory**.
3. Retrieve the employee's securely encrypted payslip password.
4. Encrypt the PDF.
5. Attach it to the email.
6. Send the email.
7. Discard the PDF from memory.
8. Record only the email delivery result.

It should **not** do:

```text
/storage/payslips/John_Banda_August_2026.pdf
```

or:

```text
public/payslips/...
```

or any other permanent server location.

---

# Portal download

The same principle should apply.

Employee clicks:

> **Download Payslip**

The system:

```text
Employee requests August 2026 payslip
             ↓
Verify authenticated employee
             ↓
Retrieve finalized payroll data
             ↓
Generate PDF in memory
             ↓
Encrypt PDF with employee password
             ↓
Stream PDF to browser
             ↓
Discard generated PDF
```

Nothing needs to be saved to disk.

---

# Very important: temporary files

I would go further and specify:

> **The application must not write generated payslip PDFs to persistent server storage, temporary directories, public directories, application storage, cache directories, or database BLOB fields.**

If the PDF library requires a file-like object, use an **in-memory stream** where supported.

If a library absolutely requires a temporary file, the implementation should use a controlled temporary location and immediately delete it after delivery, but the preferred implementation should be **memory-only generation**.

---

# What about email delivery?

There is one important distinction.

Your **system doesn't need to store the PDF**, but once the email is sent, the PDF becomes part of the employee's mailbox infrastructure.

So your application can guarantee:

> **No generated payslip PDF is retained by the accounting system/server.**

It cannot guarantee that an employee's email provider won't retain the email attachment.

That is normal and unavoidable if you're sending the payslip by email.

---

# What should the database store for the distribution log?

Only metadata:

| Field             | Example                                             |
| ----------------- | --------------------------------------------------- |
| Employee          | EMP001                                              |
| Pay Run           | PR-2026-08                                          |
| Payslip Reference | PS-2026-08-001                                      |
| Email             | [employee@example.com](mailto:employee@example.com) |
| Status            | Sent                                                |
| Sent At           | 31 Aug 2026 14:32                                   |
| Attempt Count     | 1                                                   |
| Error             | NULL                                                |

**Do not store:**

* PDF binary
* PDF path
* PDF contents
* PDF password
* PDF encryption key

---

# Password security

Because the system needs to generate the encrypted PDF, the employee's payslip password needs to be **securely recoverable**.

Therefore:

### Don't do this

```text
password_hash()
```

and expect to decrypt it later. A one-way hash cannot be recovered for PDF encryption.

### Instead

Use strong authenticated encryption for the stored payslip password, with the encryption key kept separately from the database.

Conceptually:

```text
Employee Payslip Password
          ↓
Application Encryption
          ↓
Encrypted Password
          ↓
Database
```

When generating the PDF:

```text
Database
   ↓
Encrypted Password
   ↓
Decrypt in application memory
   ↓
Encrypt PDF
   ↓
Send/Download
   ↓
Clear sensitive data from memory as far as practical
```

Payroll administrators should **never be able to view the employee's actual password**.

---

# I would also add a "Regenerate Payslip" feature

Because PDFs aren't stored, the system should be designed to regenerate them from the finalized payroll record.

For example:

```text
August 2026
Payslip Reference: PS-2026-08-001
Status: Finalized

[ View ]
[ Download PDF ]
[ Send by Email ]
```

Every time the employee downloads it, the system generates a fresh PDF from the **immutable/finalized payroll data**.

This means you don't need a payslip document repository.

---

# One critical rule

Once a pay run is finalized, the underlying payroll figures used to generate payslips should be **locked**.

Otherwise, you could have:

> Payslip downloaded on Monday → MWK 500,000

and:

> Payslip downloaded on Tuesday → MWK 550,000

because someone modified the payroll record.

So your architecture should be:

**Draft Payroll → Review → Approval → Finalization → Immutable Payroll Result → Generate Payslip**

Any correction after finalization should go through a **payroll adjustment/reversal process**, not direct editing of the finalized figures.

### Final architecture

```text
                 PAYROLL DATABASE
                       │
                       ↓
              FINALIZED PAY RUN
                       │
                       ↓
             IMMUTABLE PAYROLL DATA
                       │
              ┌────────┴────────┐
              ↓                 ↓
        EMAIL REQUEST      PORTAL REQUEST
              │                 │
              └────────┬────────┘
                       ↓
              GENERATE PDF IN RAM
                       ↓
              APPLY PDF PASSWORD
                       ↓
             ┌─────────┴─────────┐
             ↓                   ↓
          EMAIL              DOWNLOAD
             │                   │
             └─────────┬─────────┘
                       ↓
                  DISCARD PDF
                       ↓
              STORE ONLY METADATA
```

**This is the architecture I would recommend for your accounting system:** payroll information remains in the database, while the actual payslip document is **ephemeral, password-protected, and never permanently stored on your server**.


