<html>
<body>
<script>
    const path = window.location.pathname;
    window.postMessage(path.split('/').pop(), '*');
</script>
</body>
</html>
