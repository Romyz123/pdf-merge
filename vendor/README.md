# Vendor libraries (offline mode)

The PDF tool loads its third-party libraries from this `vendor/` folder **first**,
and only falls back to a CDN if a file is missing. Populate this folder by running the
download script once (requires internet, run on your own machine — not the sandbox):

- Windows (PowerShell):  `powershell -ExecutionPolicy Bypass -File vendor/download_libs.ps1`
- Linux / macOS (bash):  `bash vendor/download_libs.sh`

This downloads:
- pdf-lib, pdf.js (+ worker), SweetAlert2
- tesseract.js (+ worker, wasm core) and the English OCR model (`eng.traineddata.gz`)

After that, the tool works **fully offline / air-gapped** — no requests leave your device.

File layout expected:
```
vendor/pdf-lib.min.js
vendor/pdf.min.js
vendor/pdf.worker.min.js
vendor/sweetalert2.all.min.js
vendor/tesseract/tesseract.min.js
vendor/tesseract/worker.min.js
vendor/tesseract/tesseract-core.wasm.js
vendor/tesseract/tesseract-core.wasm
vendor/tesseract/eng.traineddata.gz
```
