from pathlib import Path
import hashlib, zipfile
root = Path(__file__).resolve().parents[1]
version = (root / 'VERSION').read_text().strip()
paths = sorted([p for folder in ('app', 'database', 'resources', 'docs') for p in (root / folder).rglob('*') if p.is_file()] + [root / n for n in ('README.md', 'LICENSE', 'SECURITY.md', 'CHANGELOG.md', 'VERSION')])
archive = root / 'downloads' / ('connect-cms-yuyuquizzes-' + version + '.zip')
archive.parent.mkdir(exist_ok=True)
manifest = []
with zipfile.ZipFile(archive, 'w', zipfile.ZIP_DEFLATED) as z:
    for p in paths:
        name = p.relative_to(root).as_posix()
        data = p.read_bytes()
        info = zipfile.ZipInfo(name, date_time=(2026, 11, 3, 0, 0, 0))
        info.compress_type = zipfile.ZIP_DEFLATED
        info.external_attr = 0o100644 << 16
        z.writestr(info, data)
        manifest.append(hashlib.sha256(data).hexdigest() + '  ' + name)
    z.writestr('SHA256SUMS.txt', '\n'.join(manifest) + '\n')
with zipfile.ZipFile(archive) as z:
    assert z.testzip() is None
    for p in paths:
        assert z.read(p.relative_to(root).as_posix()) == p.read_bytes()
print(archive)
print('Files:', len(paths) + 1)
print('SHA256:', hashlib.sha256(archive.read_bytes()).hexdigest())
