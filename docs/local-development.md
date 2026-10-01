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

คำสั่งนี้เปิด mock API ห้า process ที่ `127.0.0.1:9001` ถึง `127.0.0.1:9005` สำหรับ `cs-01`, `cs-02`, `it-01`, `eng-01` และ `sci-01` โดยอ่านเฉพาะ fixture ใน `fixtures/hosts/`.

## Run collector

```powershell
.\scripts\run-local-collector.ps1
```

ผลลัพธ์จะถูกเขียนใน `.local-cache/` ซึ่งไม่ใช่ production cache. ตรวจไฟล์ `cs-01.json`, `cs-02.json`, `it-01.json`, `eng-01.json`, `sci-01.json` และ `metadata/*.json` เพื่อดูผลแยกตาม host.

## Demo data

ชุด Demo มี 5 VM และนักศึกษา 80 คน แบ่ง VM ละ 16 คน สามารถสร้าง fixture เดิมซ้ำได้ด้วย:

```powershell
docker run --rm -v "${PWD}:/workspace" -w /workspace python:3.12-alpine python scripts/generate-local-demo-fixtures.py
```

หลังแก้ fixture ให้รัน collector ใหม่เพื่ออัปเดต `.local-cache/`.

## Safety rules

- ใช้ `config/hosts.local.example.json` เท่านั้นบน Local
- ห้ามเปลี่ยน `base_url` เป็น production โดยไม่ตั้งใจ
- ห้ามใส่ token จริงใน fixture หรือ script
- ห้ามใช้ production cache directory หรือ SSH key บน Local
- หากต้องหยุด mock API ให้ใช้ process IDs ที่ script แสดงผล
