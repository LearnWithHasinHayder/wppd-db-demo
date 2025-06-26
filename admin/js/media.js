document.getElementById('db-demo-media-btn').addEventListener('click', function () {
    let frame;

    if (frame) {
        frame.open()
        return
    }

    frame = wp.media({
        title: "Select Media",
        button: { text: "Use this image" },
        multiple: false
    })

    frame.on('select', function () {
        const attachment = frame.state().get('selection').first().toJSON()
        document.getElementById('media_url').value = attachment.url
        document.getElementById('db-demo-media-preview').innerHTML = `<img src=${attachment.url} style="max-width:200px;" />`
    })

    frame.open()
})