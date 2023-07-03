function picwithexif_getInvert(num){
    if (num >= 1){
        return num;
    }else if (num > 0){
        return "1/" + Math.round((1 / num));
    }else{
        return num;
    }
}

function picwithexif_call(id){
    EXIF.getData(document.getElementById(id + '_pic'), function(){
        var make = EXIF.getTag(this, "Make");
        var model = EXIF.getTag(this, "Model");
        if (model.startsWith(make)){
            make = '';
        }
        var tags = make + ' ' + model + ' ' + EXIF.getTag(this, "FocalLength") + "mm " + picwithexif_getInvert(EXIF.getTag(this, "ExposureTime")) + 's f/' + EXIF.getTag(this, "FNumber");
        $('#' + id + '_exif').html(tags);
    });
}