(function ($) {
  jQuery.fn.yiiWebUploader = function (options, pluginOptions, pluginEvents) {
    var def = {
      containerId: '#w1',
      pickBtnId: '#pick-btn',
      hiddenInputContainerId: '#hidden-container',
      hiddenInputName: 'name',
      fileSplitToArray: false,
      fileExplodeBy: ',',
      deleteUrl: '/file/delete',
      realDelete: true,
      messageMap: {},
      alertMsg: function (msg, type) {
        alert(msg);
      }
    };
    $.extend(def, options);
    var $container = $(def.containerId);
    var uploader = null;

    var methods = {
      init: function () {
        // 初始化组件
        methods.webUploaderInit();
        // 触发文件选择
        $container.find(def.pickBtnId + '-trigger').click(function () {
          $container.find(def.pickBtnId).find('input[type=file]').trigger('click');
        });
        // 删除文件
        $container.find('.file-list')
          .on('mouseover', '.file-list-item.complete', function () {
            $(this).addClass('delete');
          })
          .on('mouseout', '.file-list-item.complete', function () {
            $(this).removeClass('delete');
          })
          .on('click', '.file-list-item.complete.delete', function () {
            $(this).remove();
            if ($(this).attr('data-origin')) {
              // 此处无法动态修改 fileNumLimit 的值，通过事件 beforeFileQueued 解决
              // @link https://github.com/fex-team/webuploader/issues/2927
              // var fileNumLimit = uploader.option('fileNumLimit');
              // uploader.option('fileNumLimit', fileNumLimit + 1);
            } else {
              uploader.removeFile($(this).attr('data-file-id'));
            }
            if (def.realDelete) {
              $.post(def.deleteUrl, {filename: $(this).attr('data-url')});
            }
            methods.checkPickStatus();
            methods.solveHiddenInput();
          });
      },
      webUploaderInit: function () {
        var options = {
          auto: true,
          server: '/file/upload',
          pick: {
            multiple: false
          },
          fileNumLimit: 1,
          fileSingleSizeLimit: 5242880
        };
        if (pluginOptions) {
          $.extend(options, pluginOptions);
        }
        if (typeof options.pick === 'object') {
          options.pick.id = def.containerId + ' ' + def.pickBtnId;
        }
        uploader = new WebUploader.Uploader(options);
        $.each(uploaderEvents, function (event, handler) {
          uploader.on(event, handler);
        });
        if (pluginEvents) {
          $.each(pluginEvents, function (event, handler) {
            uploader.on(event, handler);
          });
        }
      },
      getListItemContainer: function (file) {
        return $container.find('[data-file-id=' + file.id + ']');
      },
      getCurrentFileListCount: function () {
        return $container.find('.file-list-item').length - 2;
      },
      // 检查是否可以继续添加
      checkPickStatus: function () {
        if (methods.getCurrentFileListCount() >= (uploader.option('fileNumLimit'))) {
          $container.find('.file-list-item.pick').hide();
        } else {
          $container.find('.file-list-item.pick').show();
        }
      },
      // 处理隐藏域的值
      solveHiddenInput: function () {
        var url = [];
        $container.find('.file-list-item.complete').each(function () {
          url.push($(this).attr('data-url'));
        });
        if (def.fileSplitToArray) {
          var html = [];
          $.each(url, function (item) {
            html.push('<input type="hidden" name="' + def.hiddenInputName + '[]" value="' + item + '">');
          });
          $container.find(def.hiddenInputContainerId).html(html.join("\n"));
        } else {
          $container.find(def.hiddenInputContainerId).html('<input type="hidden" name="' + def.hiddenInputName + '" value="' + url.join(def.fileExplodeBy) + '">')
        }
      },
      alertMsg: function (msg, type) {
        if (def.messageMap[msg]) {
          msg = def.messageMap[msg];
        }
        def.alertMsg(msg, type);
      },
      uploadError: function (file, msg) {
        methods.alertMsg(msg, 'error');
        uploader.removeFile(file.id, true);
        methods.getListItemContainer(file).remove();
      }
    };

    var uploaderEvents = {
      fileQueued: function (file) {
        var template = $container.find('.file-list-item.template').clone();
        template.removeClass('template')
          .attr('data-file-id', file.id)
          .show();
        $container.find('.file-list-item.pick').before(template);
        uploader.makeThumb(file, function (error, src) {
          if (error) {
            return;
          }
          template.find('img').attr('src', src);
        });
        methods.checkPickStatus();
      },
      uploadProgress: function (file, percentage) {
        var item = methods.getListItemContainer(file);
        if (!item.find('.progress').length) {
          item.append('<div class="progress">0%</div>');
        }
        item.find('.progress').text(Math.round(percentage * 10000) / 100 + '%');
      },
      uploadSuccess: function (file, response) {
        if (response.code !== 200) {
          methods.uploadError(file, response.msg);
          return;
        }
        var item = methods.getListItemContainer(file);
        item.addClass('complete');
        var url = response.data.url;
        item.attr('data-url', url);
        if (file.type.indexOf('image') === 0) {
          item.find('img').attr('src', url);
        } else {
          item.find('img').remove();
          item.append('<span class="text">' + url + '</span>');
        }
      },
      uploadError: function (file) {
        methods.uploadError(file, 'MSG_UPLOAD_ERROR');
      },
      uploadComplete: function (file) {
        methods.getListItemContainer(file).find('.progress').remove();
        methods.checkPickStatus();
        methods.solveHiddenInput();
      },
      error: function (type) {
        methods.alertMsg(type, 'error');
      },
      beforeFileQueued: function (file) {
        // @link https://github.com/fex-team/webuploader/issues/2973
        // @link https://github.com/fex-team/webuploader/issues/2927
        var max = uploader.option('fileNumLimit');
        if ((methods.getCurrentFileListCount() + 1) > max) {
          uploader.trigger('error', 'Q_EXCEED_NUM_LIMIT', max, file);
          return false;
        }
        return true;
      }
    };

    methods.init.apply(this);
    return this;
  }
})(jQuery);
