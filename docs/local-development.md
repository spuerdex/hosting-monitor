# Local Multi-host Monitoring

## Requirements

- PHP 8.3 or newer
- Python 3.12 or newer
- PowerShell 5.1 or newer

## Start mock APIs

จาก project root:

```powershell
.\scripts\start-local-mock-api.ps1
```

คำสั่งนี้เปิด mock API สอง process ที่ `127.0.0.1:9001` และ `127.0.0.1:9002` โดยอ่านเฉพาะ fixture ใน `fixtures/hosts/`.

## Run collector

```powershell
.\scripts\run-local-collector.ps1
```

ผลลัพธ์จะถูกเขียนใน `.local-cache/` ซึ่งไม่ใช่ production cache. ตรวจไฟล์ `cs.json`, `it.json` และ `metadata/*.json` เพื่อดูผลแยกตาม host.

## Safety rules

- ใช้ `config/hosts.local.example.json` เท่านั้นบน Local
- ห้ามเปลี่ยน `base_url` เป็น production โดยไม่ตั้งใจ
- ห้ามใส่ token จริงใน fixture หรือ script
- ห้ามใช้ production cache directory หรือ SSH key บน Local
- หากต้องหยุด mock API ให้ใช้ process IDs ที่ script แสดงผล
