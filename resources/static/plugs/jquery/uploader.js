define(function () {
    return function (element, InitHandler, UploadedHandler, CompleteHandler) {
        var exts = $(element).data('type') || '*';
        var field = $(element).data('field') || '';
        var showProgress = $(element).attr('showProgress') !== undefined;
        var uploadOptions = {
            proindex: 0,
            elem: element,
            auto: false,
            accept: 'file',
            exts: exts.split(',').join('|'),
            choose: function (obj) {
                obj.preview(function(index, file) {
                    $.ajax({
                        type: "post", url: "/upload/uploadCheck",data: {fileName: file.name, fileSize: file.size},
                        success: function (ret) {
                            if (ret.code == '0') {
                                $.msg.error(ret.msg);
                                return;
                            };
                            if (showProgress) {
                                var progress = $('<div class="layui-progress margin-top-10">')
                                    .attr({'lay-filter': 'progress-'+field, 'lay-showPercent':'true'})
                                    .append($('<div class="layui-progress-bar">'));
                                $(element).after(progress);
                            }
                            if (ret.data.uploadId) {
                                shard(ret.data,file, 'progress-'+field)
                            } else {
                                uploader(ret.data,file, 'progress-'+field);
                            }
                            
                        }
                    });
                });
            }
        };
        layui.upload.render(uploadOptions);
        function uploader(uploadParam,file, progressFilter) {
            var header = uploadParam.header ? keyValToObj(uploadParam.header) : {};
            var formData = new FormData();
            for(let item of uploadParam.body) {
                formData.append(item.key, item.value);
            }
            formData.append(uploadParam.fileFieldName?uploadParam.fileFieldName:'file', file);
            $.ajax({
                type: "post",url: uploadParam.url,header: header,data: formData,
                contentType: false,processData: false,
                xhr: function() {
                    var xhr = $.ajaxSettings.xhr();
                    if (xhr.upload) {
                        xhr.upload.addEventListener('progress', function(e) {
                            layui.element.progress(progressFilter, Math.floor(e.loaded/e.total*100)+'%');
                        }, false);
                    }
                    return xhr;
                },
                success: function (ret) {
                    $('[name="'+field+'"]').val(uploadParam.filePath).trigger('change');
                    $.msg.success('上传成功!');
                },
                error: function(e) {
                    $.msg.error('文件验证失败,请重试');
                }
            });
        }
        function shard(uploadParam, file, progressFilter) {
            var error = false;
            var shardSize = uploadParam.shardSize;
            var shardNumber = Math.ceil(file.size / shardSize);
            var shardList = Array.from(Array(shardNumber), (v,k) =>k+1);
            var shardOptionsList = [];
            var taskNumber = -1;
            var etags = [];
            var totalSize = file.size;
            var completeSize = [];

            var getOptions = function(beginPartNumber, endPartNumber) {
                $.ajax({
                    type: 'post',
                    url: '/upload/getShardOptions',
                    data: {uploadId: uploadParam.uploadId, beginPartNumber:beginPartNumber,endPartNumber:endPartNumber},
                    success: function(ret) {
                        if (ret.code == '1') {
                            shardOptionsList = shardOptionsList.concat(ret.data);
                            initUpload();
                        } else {
                            error = true;
                            $.msg.error(ret.msg);
                        }
                    },
                    error: function() {
                        error = true
                    }
                });
            }

            var shardUpload = function (url, header, data, partNumber) {
                if (error) return
                taskNumber+= (taskNumber==-1?2:1);
                completeSize[partNumber] = 0;
                $.ajax({
                    type: "put",url: url,data: data,headers: header,processData: false,
                    contentType: 'application/octet-stream',
                    xhr: function() {
                        var xhr = $.ajaxSettings.xhr();
                        if (xhr.upload) xhr.upload.addEventListener('progress', function (e) {
                            completeSize[partNumber] = e.loaded;
                            layui.element.progress(progressFilter, Math.floor(eval(completeSize.join('+'))/totalSize*100) + '%');
                        });
                        return xhr;
                    },
                    success: function (response,status, xhr) {
                        taskNumber--;
                        let etag = xhr.getResponseHeader('ETag') || response.etag;
                        etags[partNumber] = etag;
                        initUpload();
                    },
                    error: function(err) {
                        taskNumber--;
                        error = true;
                    },
                });
            }

            var initUpload = function () {
                if (error) return;
                if (shardOptionsList.length >= 1) {
                    let options = shardOptionsList.shift();
                    shardUpload(options.url, keyValToObj(options.header ? options.header : []), file.slice(shardSize*(options.partNumber-1), shardSize*options.partNumber), options.partNumber);
                }
                if (shardOptionsList.length == 0 && shardList.length == 0 && taskNumber == 0) {
                    shardComplete();
                }
                if (shardOptionsList.length <= 1) {
                    if (shardList.length <= 0) return;
                    let beginPartNumber = shardList[0];
                    let endPartNumber;
                    if (shardList.length >= 4) {
                        endPartNumber = beginPartNumber + 3;
                        shardList.splice(0, 4);
                    } else {
                        endPartNumber = beginPartNumber + shardList.length -1;
                        shardList.splice(0, shardList.length);
                    }
                    getOptions(beginPartNumber, endPartNumber);
                }
                if (taskNumber < 4) initUpload();
            }
            
            var shardComplete = function () {
                $.ajax({
                    type: "post",
                    url: "/upload/complateShardUpload",
                    data: {uploadId: uploadParam.uploadId, etags: etags},
                    success: function (response) {
                        if (response.code == 1) {
                            $.msg.success('上传成功');
                            $('[name="'+field+'"]').val(uploadParam.filePath).trigger('change');
                        } else {
                            $.msg.error(response.data);
                        }
                    }
                });
            }
            initUpload();
        }
        function keyValToObj(arr) {
            let obj = {};
            for(let item of arr) {
                if (['Content-Length', 'Host'].indexOf(item.key) != -1) continue;
                obj[item.key] = item.value;
            }
            return obj;
        }
    };
});