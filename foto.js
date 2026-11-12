function downloadPhotoLocally() {
    const canvas = document.getElementById('photoCanvas');
    const imageURL = canvas.toDataURL('image/jpeg', 0.9);
    
    // Buat elemen <a> sementara untuk memicu download
    const downloadLink = document.createElement('a');
    downloadLink.href = imageURL;
    downloadLink.download = `GPS_Photo_${Date.now()}.jpg`;
    
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}