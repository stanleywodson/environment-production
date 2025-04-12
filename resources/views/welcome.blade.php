<html>
<head>
    <title>Upload de Arquivo</title>
</head>
<body>
    <form action="/upload" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="file" name="file" required>
        <button type="submit">Upload</button>
    </form>
</body>
</html>
