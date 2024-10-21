import { defineComponent, ref, computed, pushScopeId, popScopeId, openBlock, createBlock, Fragment, createVNode, renderSlot, withScopeId, resolveComponent, toDisplayString, createCommentVNode, resolveDynamicComponent, renderList, onMounted, Teleport } from 'vue';

// vue
var script = defineComponent({
  props: {
    rippleColor: {
      type: String,
      default: 'rgb(255, 255, 255)'
    }
  },

  // eslint-disable-next-line
  setup(props) {
    // refs
    const rippleEffect = ref(null); // computed

    const cssProperties = computed(() => {
      return `--ripple-color: ${props.rippleColor};`;
    }); // methods

    const createRipple = event => {
      const button = event.currentTarget;

      if (button) {
        const circle = document.createElement('span');
        const diameter = Math.max(button.clientWidth, button.clientHeight);
        const radius = diameter / 2;
        circle.style.width = circle.style.height = `${diameter}px`;
        const buttonElement = button.getBoundingClientRect();
        circle.style.left = `
                    ${event.clientX - buttonElement.left - radius}px
                `;
        circle.style.top = `
                    ${event.clientY - buttonElement.top - radius}px
                `;
        circle.classList.add('ripple');

        if (rippleEffect.value) {
          const ripple = button.getElementsByClassName('ripple')[0];

          if (ripple) {
            ripple.remove();
          }
        }

        button.appendChild(circle);
      }
    };

    return {
      props,
      createRipple,
      rippleEffect,
      cssProperties
    };
  }

});

const _withId = /*#__PURE__*/withScopeId("data-v-3c6cb756");

pushScopeId("data-v-3c6cb756");

const _hoisted_1 = {
  class: "content"
};

popScopeId();

const render = /*#__PURE__*/_withId((_ctx, _cache, $props, $setup, $data, $options) => {
  return openBlock(), createBlock(Fragment, null, [createVNode("div", {
    class: "ripple-effect",
    style: _ctx.cssProperties,
    onMousedown: _cache[1] || (_cache[1] = (...args) => _ctx.createRipple && _ctx.createRipple(...args)),
    ref: "rippleEffect"
  }, null, 36), createVNode("div", _hoisted_1, [renderSlot(_ctx.$slots, "default")])], 64);
});

function styleInject(css, ref) {
  if ( ref === void 0 ) ref = {};
  var insertAt = ref.insertAt;

  if (!css || typeof document === 'undefined') { return; }

  var head = document.head || document.getElementsByTagName('head')[0];
  var style = document.createElement('style');
  style.type = 'text/css';

  if (insertAt === 'top') {
    if (head.firstChild) {
      head.insertBefore(style, head.firstChild);
    } else {
      head.appendChild(style);
    }
  } else {
    head.appendChild(style);
  }

  if (style.styleSheet) {
    style.styleSheet.cssText = css;
  } else {
    style.appendChild(document.createTextNode(css));
  }
}

var css_248z = "\n/* ripple */\n.ripple-effect[data-v-3c6cb756] {\n    display: inline-flex;\n    position: absolute;\n    top: 0;\n    left: 0;\n    right: 0;\n    bottom: 0;\n    outline: 0;\n    border-radius: 4px;\n    cursor: pointer;\n    white-space: nowrap;\n    box-sizing: border-box;\n    text-decoration: none;\n    flex: 1;\n    width: 100%;\n    height: 100%;\n    transition: background 100ms, box-shadow 300ms;\n}\n.content[data-v-3c6cb756] {\n    z-index: 10;\n    pointer-events: none;\n}\n";
styleInject(css_248z);

var css_248z$1 = "\nspan.ripple {\n    --visible-opacity: 0.2;\n    pointer-events: none;\n    display: flex;\n    position: absolute;\n    border-radius: 50%;\n    pointer-events: none;\n    background-color: rgba(255, 255, 255, 0);\n    box-shadow: 0 0 5px 5px var(--ripple-color);\n    animation: ripple 450ms ease-in;\n    transform: scale(4);\n    transition: background-color 0.2s;\n    opacity: 0.2;\n}\n.ripple-effect:active:hover span.ripple {\n    background-color: var(--ripple-color) !important;\n}\n@keyframes ripple {\n0% {\n        background-color: var(--ripple-color);\n        transform: scale(0);\n}\n66% {\n        background-color: var(--ripple-color);\n        transform: scale(4);\n}\n100% {\n        background-color: rgba(255, 255, 255, 0);\n}\n}\n";
styleInject(css_248z$1);

script.render = render;
script.__scopeId = "data-v-3c6cb756";

// vue
var script$1 = defineComponent({
  components: {
    VmRipple: script
  },
  props: {
    type: {
      type: String,
      default: 'button'
    },
    icon: {
      type: String,
      default: 'fal fa-coffee'
    },
    label: {
      type: String,
      default: ''
    },
    isSmall: {
      type: Boolean,
      default: false
    },
    primaryColor: {
      type: String,
      default: 'rgb(98, 0, 238)'
    },
    accentColor: {
      type: String,
      default: 'rgb(255, 255, 255)'
    },
    elevation: {
      type: Number,
      default: 1
    }
  },

  // eslint-disable-next-line
  setup(props) {
    // computed
    const cssProperties = computed(() => {
      let css = `--primary-color: ${props.primaryColor};` + `--text-color: ${props.accentColor};`;

      if (!props.label) {
        css += `--ab-width: var(--size);` + `--ab-padding: 0;`;
      }

      if (props.elevation !== null) {
        css += `--default-elevation: var(--elevation-${props.elevation});` + `--focus-elevation: var(--elevation-${props.elevation + 2});` + `--active-elevation: var(--elevation-${props.elevation + 6});`;
      }

      return css;
    });
    return {
      props,
      cssProperties
    };
  }

});

const _withId$1 = /*#__PURE__*/withScopeId("data-v-82c27306");

pushScopeId("data-v-82c27306");

const _hoisted_1$1 = /*#__PURE__*/createVNode("div", {
  class: "btn-overlay"
}, null, -1);

const _hoisted_2 = {
  class: "content"
};
const _hoisted_3 = {
  key: 0,
  class: "label-text"
};

popScopeId();

const render$1 = /*#__PURE__*/_withId$1((_ctx, _cache, $props, $setup, $data, $options) => {
  const _component_vm_ripple = resolveComponent("vm-ripple");

  return openBlock(), createBlock("button", {
    class: ["vm-action-btn", {
      small: _ctx.isSmall
    }],
    style: _ctx.cssProperties,
    type: _ctx.type
  }, [createVNode(_component_vm_ripple, {
    rippleColor: _ctx.props.accentColor
  }, {
    default: _withId$1(() => [_hoisted_1$1, createVNode("div", _hoisted_2, [createVNode("i", {
      class: _ctx.props.icon
    }, null, 2), _ctx.props.label ? (openBlock(), createBlock("div", _hoisted_3, toDisplayString(_ctx.props.label), 1)) : createCommentVNode("", true)])]),
    _: 1
  }, 8, ["rippleColor"])], 14, ["type"]);
});

var css_248z$2 = "\n.vm-action-btn[data-v-82c27306] {\n    --size: 45px;\n    --ab-padding: 16px;\n    width: var(--ab-width);\n    height: var(--size);\n    min-width: var(--ab-width);\n    min-height: var(--size);\n    max-width: var(--ab-width);\n    max-height: var(--size);\n    user-select: none;\n    display: inline-flex;\n    justify-content: center;\n    align-items: center;\n    position: relative;\n    overflow: hidden;\n    transition: background 400ms;\n    color: var(--text-color);\n    background: var(--primary-color);\n    font-size: 1.6rem;\n    outline: 0;\n    border: 0;\n    border-radius: calc(var(--size) / 2);\n    box-shadow: var(--elevation-shadow);\n    padding: 0 var(--ab-padding);\n    cursor: pointer;\n    box-shadow: var(--default-elevation);\n    transition: background 100ms, box-shadow 300ms;\n    font-family: 'Roboto', sans-serif;\n    font-weight: 500;\n    letter-spacing: 1.25px;\n}\n.label-text[data-v-82c27306] {\n    font-size: 0.88rem;\n}\n.vm-action-btn[data-v-82c27306]:hover,\n.vm-action-btn[data-v-82c27306]:focus {\n    box-shadow: var(--focus-elevation);\n}\n.vm-action-btn[data-v-82c27306]:active:focus {\n    box-shadow: var(--active-elevation);\n}\n.vm-action-btn:hover .btn-overlay[data-v-82c27306] {\n    background: rgba(255, 255, 255, 0.05);\n}\n.vm-action-btn:focus .btn-overlay[data-v-82c27306] {\n    background: rgba(255, 255, 255, 0.1);\n}\n.small[data-v-82c27306] {\n    --size: 35px;\n    font-size: 1.4rem;\n}\n.btn-overlay[data-v-82c27306] {\n    position: absolute;\n    top: 0;\n    left: 0;\n    right: 0;\n    bottom: 0;\n    background: rgba(255, 255, 255, 0);\n    transition-duration: 0.1s;\n}\n.content[data-v-82c27306] {\n    z-index: 1;\n    display: flex;\n    justify-content: center;\n    width: 100%;\n}\n";
styleInject(css_248z$2);

script$1.render = render$1;
script$1.__scopeId = "data-v-82c27306";

// vue
var script$2 = defineComponent({
  props: {
    elevation: {
      type: Number,
      default: 2
    }
  },

  // eslint-disable-next-line
  setup(props) {
    return {
      props
    };
  }

});

const _withId$2 = /*#__PURE__*/withScopeId("data-v-2ddee2f9");

const render$2 = /*#__PURE__*/_withId$2((_ctx, _cache, $props, $setup, $data, $options) => {
  return openBlock(), createBlock("div", {
    class: ["app-bar", [`elevation-${_ctx.props.elevation}`, `elevation-background-${_ctx.props.elevation}`]]
  }, [renderSlot(_ctx.$slots, "default")], 2);
});

var css_248z$3 = "\n.app-bar[data-v-2ddee2f9] {\n    min-height: 4rem;\n    display: flex;\n    width: 100%;\n}\n";
styleInject(css_248z$3);

script$2.render = render$2;
script$2.__scopeId = "data-v-2ddee2f9";

// vue
var script$3 = defineComponent({
  components: {
    VmRipple: script
  },
  props: {
    type: {
      type: String,
      default: 'button'
    },
    variant: {
      type: String,
      validator: prop => ['contained', 'outlined', 'text'].includes(prop),
      default: 'contained'
    },
    routerPath: {
      type: String,
      default: ''
    },
    isDisabled: {
      type: Boolean,
      default: false
    },
    primaryColor: {
      type: String,
      default: 'rgb(98, 0, 238)'
    },
    accentColor: {
      type: String,
      default: 'rgb(255, 255, 255)'
    },
    elevation: {
      type: Number,
      default: 2
    }
  },

  // eslint-disable-next-line
  setup(props) {
    // computed
    const cssProperties = computed(() => {
      let css = `--primary-color: ${props.primaryColor};` + `--text-color: ${props.accentColor};`;

      if (props.elevation !== null) {
        css += `--default-elevation: var(--elevation-${props.elevation});` + `--focus-elevation: var(--elevation-${props.elevation + 2});` + `--active-elevation: var(--elevation-${props.elevation + 6});`;
      }

      return css;
    });
    return {
      props,
      cssProperties
    };
  }

});

const _withId$3 = /*#__PURE__*/withScopeId("data-v-9fecb26c");

pushScopeId("data-v-9fecb26c");

const _hoisted_1$2 = /*#__PURE__*/createVNode("div", {
  class: "btn-overlay"
}, null, -1);

popScopeId();

const render$3 = /*#__PURE__*/_withId$3((_ctx, _cache, $props, $setup, $data, $options) => {
  const _component_vm_ripple = resolveComponent("vm-ripple");

  return openBlock(), createBlock(resolveDynamicComponent(_ctx.routerPath ? 'router-link' : 'button'), {
    class: ["vm-btn", [`btn-${_ctx.props.variant}`, {
      disabled: _ctx.isDisabled
    }]],
    style: _ctx.cssProperties,
    type: _ctx.props.type,
    to: _ctx.routerPath
  }, {
    default: _withId$3(() => [_hoisted_1$2, createVNode(_component_vm_ripple, {
      rippleColor: _ctx.props.variant !== 'contained' ? _ctx.props.primaryColor : _ctx.props.accentColor
    }, {
      default: _withId$3(() => [renderSlot(_ctx.$slots, "default")]),
      _: 3
    }, 8, ["rippleColor"])]),
    _: 1
  }, 8, ["class", "style", "type", "to"]);
});

var css_248z$4 = "\n/* contained */\n.btn-contained[data-v-9fecb26c] {\n    color: var(--text-color);\n    background: var(--primary-color);\n    box-shadow: var(--default-elevation);\n    border: 0;\n    padding: 10px 16px;\n}\n.btn-overlay[data-v-9fecb26c] {\n    position: absolute;\n    top: 0;\n    left: 0;\n    right: 0;\n    bottom: 0;\n    background: rgba(255, 255, 255, 0);\n    transition-duration: 0.1s;\n}\n.btn-contained:hover .btn-overlay[data-v-9fecb26c] {\n    background: rgba(255, 255, 255, 0.05);\n}\n.btn-contained:focus .btn-overlay[data-v-9fecb26c] {\n    background: rgba(255, 255, 255, 0.15);\n}\n\n/* outlined and text */\n.btn-outlined[data-v-9fecb26c] {\n    background: none;\n    color: var(--primary-color);\n    border: 1px solid var(--primary-color);\n    padding: 10px 15px;\n}\n.btn-text[data-v-9fecb26c] {\n    background: none;\n    color: var(--primary-color);\n    border: 0;\n    padding: 10px 16px;\n}\n.btn-text .btn-overlay[data-v-9fecb26c],\n.btn-outlined .btn-overlay[data-v-9fecb26c] {\n    opacity: 0.1;\n}\n.btn-text:hover .btn-overlay[data-v-9fecb26c],\n.btn-outlined:hover .btn-overlay[data-v-9fecb26c] {\n    background: var(--primary-color);\n}\n.btn-text:focus .btn-overlay[data-v-9fecb26c],\n.btn-outlined:focus .btn-overlay[data-v-9fecb26c] {\n    background: var(--primary-color);\n    opacity: 0.2;\n}\n.vm-btn[data-v-9fecb26c] {\n    font-family: 'Roboto', sans-serif;\n    user-select: none;\n    display: inline-flex;\n    align-items: center;\n    position: relative;\n    overflow: hidden;\n    font-size: 0.88rem;\n    font-weight: 500;\n    letter-spacing: 1.25px;\n    outline: 0;\n    border-radius: 4px;\n    cursor: pointer;\n    white-space: nowrap;\n    box-sizing: border-box;\n    text-decoration: none;\n    transition: background 100ms, box-shadow 300ms;\n}\n.btn-contained[data-v-9fecb26c]:hover,\n.btn-contained[data-v-9fecb26c]:focus {\n    box-shadow: var(--focus-elevation);\n}\n.btn-contained[data-v-9fecb26c]:active:hover {\n    box-shadow: var(--active-elevation);\n}\n.disabled[data-v-9fecb26c] {\n    opacity: 0.5;\n    pointer-events: none;\n}\n";
styleInject(css_248z$4);

script$3.render = render$3;
script$3.__scopeId = "data-v-9fecb26c";

// vue
var script$4 = defineComponent({
  props: {
    elevation: {
      type: Number,
      default: 2
    },
    borderRadius: {
      type: Number,
      default: 5
    }
  },

  // eslint-disable-next-line
  setup(props) {
    // computed
    const cssProperties = computed(() => {
      return `--card-border-radius: ${props.borderRadius}px;`;
    });
    return {
      props,
      cssProperties
    };
  }

});

const _withId$4 = /*#__PURE__*/withScopeId("data-v-114efd9e");

const render$4 = /*#__PURE__*/_withId$4((_ctx, _cache, $props, $setup, $data, $options) => {
  return openBlock(), createBlock("div", {
    class: ["card", [`elevation-${_ctx.props.elevation}`, `elevation-background-${_ctx.props.elevation}`]],
    style: _ctx.cssProperties
  }, [renderSlot(_ctx.$slots, "default")], 6);
});

var css_248z$5 = "\n.card[data-v-114efd9e] {\n    position: relative;\n    border-radius: var(--card-border-radius);\n    overflow: hidden;\n    transition: box-shadow 0.2s;\n}\n";
styleInject(css_248z$5);

script$4.render = render$4;
script$4.__scopeId = "data-v-114efd9e";

// vue
var script$5 = defineComponent({
  // eslint-disable-next-line
  setup(props) {
    return {
      props
    };
  }

});

const _withId$5 = /*#__PURE__*/withScopeId("data-v-e2fc2772");

pushScopeId("data-v-e2fc2772");

const _hoisted_1$3 = {
  class: "card-content"
};

popScopeId();

const render$5 = /*#__PURE__*/_withId$5((_ctx, _cache, $props, $setup, $data, $options) => {
  return openBlock(), createBlock("div", _hoisted_1$3, [renderSlot(_ctx.$slots, "default")]);
});

var css_248z$6 = "\n.card-content[data-v-e2fc2772] {\n    padding: 15px 20px;\n}\n";
styleInject(css_248z$6);

script$5.render = render$5;
script$5.__scopeId = "data-v-e2fc2772";

// vue
var script$6 = defineComponent({
  // eslint-disable-next-line
  setup(props) {
    return {
      props
    };
  }

});

const _withId$6 = /*#__PURE__*/withScopeId("data-v-4bb1fe1a");

pushScopeId("data-v-4bb1fe1a");

const _hoisted_1$4 = {
  class: "card-header"
};

popScopeId();

const render$6 = /*#__PURE__*/_withId$6((_ctx, _cache, $props, $setup, $data, $options) => {
  return openBlock(), createBlock("div", _hoisted_1$4, [renderSlot(_ctx.$slots, "default")]);
});

var css_248z$7 = "\n.card-header[data-v-4bb1fe1a] {\n    padding: 15px 20px;\n    font-size: 1.2rem;\n    font-weight: 500;\n}\n";
styleInject(css_248z$7);

script$6.render = render$6;
script$6.__scopeId = "data-v-4bb1fe1a";

// vue
var script$7 = defineComponent({
  setup() {
    return {};
  }

});

const _withId$7 = /*#__PURE__*/withScopeId("data-v-bebb5804");

pushScopeId("data-v-bebb5804");

const _hoisted_1$5 = {
  class: "container"
};
const _hoisted_2$1 = {
  class: "content"
};

popScopeId();

const render$7 = /*#__PURE__*/_withId$7((_ctx, _cache, $props, $setup, $data, $options) => {
  return openBlock(), createBlock("div", _hoisted_1$5, [createVNode("div", _hoisted_2$1, [renderSlot(_ctx.$slots, "default")])]);
});

var css_248z$8 = "\n.container[data-v-bebb5804] {\n    display: flex;\n    justify-content: center;\n    padding: 0 calc(var(--default-margin) * 4);\n}\n.content[data-v-bebb5804] {\n    flex: 1;\n    max-width: 1200px;\n    width: 100%;\n}\n";
styleInject(css_248z$8);

script$7.render = render$7;
script$7.__scopeId = "data-v-bebb5804";

// vue
var script$8 = defineComponent({
  props: {
    modelValue: {
      type: [String, Number]
    },
    variant: {
      type: String,
      validator: prop => ['filled', 'outlined'].includes(prop),
      default: 'filled'
    },
    label: {
      type: String,
      default: 'Default'
    }
  },

  // eslint-disable-next-line
  setup(props, context) {
    // refs
    const datePicker = ref(null); // methods

    const updateValue = e => {
      const val = e.target.value;
      context.emit('update:modelValue', val);
    };

    return {
      props,
      datePicker,
      updateValue
    };
  }

});

const _withId$8 = /*#__PURE__*/withScopeId("data-v-7427c44c");

pushScopeId("data-v-7427c44c");

const _hoisted_1$6 = /*#__PURE__*/createVNode("div", {
  class: "date-picker-left"
}, null, -1);

const _hoisted_2$2 = {
  class: "date-picker-notch date-picker-notch-filled"
};

const _hoisted_3$1 = /*#__PURE__*/createVNode("div", {
  class: "date-picker-right"
}, null, -1);

popScopeId();

const render$8 = /*#__PURE__*/_withId$8((_ctx, _cache, $props, $setup, $data, $options) => {
  return openBlock(), createBlock("div", {
    class: ["date-picker", 'date-picker-' + _ctx.props.variant]
  }, [createVNode("input", {
    class: "date-picker-input",
    type: "date",
    ref: "datePicker",
    value: _ctx.modelValue,
    onInput: _cache[1] || (_cache[1] = (...args) => _ctx.updateValue && _ctx.updateValue(...args))
  }, null, 40, ["value"]), _hoisted_1$6, createVNode("div", _hoisted_2$2, [createVNode("div", {
    class: ["date-picker-label", {
      'date-picker-label-filled': _ctx.isInputFilled
    }]
  }, toDisplayString(_ctx.props.label), 3)]), _hoisted_3$1], 2);
});

var css_248z$9 = "\n.date-picker[data-v-7427c44c] {\n    --font-size: 1rem;\n    --label-padding: 6px;\n    --horizontal-padding: 16px;\n    --border: 1px solid rgba(var(--default-color), 0.42);\n    --height: calc(\n        var(--font-size) + (var(--padding-top) + var(--padding-bottom)) + 2px\n    );\n    display: flex;\n    align-items: center;\n    width: 100%;\n    position: relative;\n}\n.date-picker-filled[data-v-7427c44c] {\n    --padding-top: 24px;\n    --padding-bottom: calc(38px - var(--padding-top));\n    transition: background 0.2s;\n}\n.date-picker-filled[data-v-7427c44c]:hover {\n    --padding-bottom: calc(38px - var(--padding-top));\n}\n.date-picker-outlined[data-v-7427c44c] {\n    --padding-top: 18px;\n    --padding-bottom: 18px;\n}\n.date-picker-outlined:hover .date-picker-left[data-v-7427c44c],\n.date-picker-outlined:hover .date-picker-notch[data-v-7427c44c],\n.date-picker-outlined:hover .date-picker-right[data-v-7427c44c] {\n    --border: 1px solid rgba(var(--default-color), 0.87);\n}\n.date-picker-filled .date-picker-left[data-v-7427c44c],\n.date-picker-filled .date-picker-notch[data-v-7427c44c],\n.date-picker-filled .date-picker-right[data-v-7427c44c] {\n    background: rgba(var(--default-color), 0.03);\n}\n.date-picker-filled:hover .date-picker-left[data-v-7427c44c],\n.date-picker-filled:hover .date-picker-notch[data-v-7427c44c],\n.date-picker-filled:hover .date-picker-right[data-v-7427c44c] {\n    background: rgba(var(--default-color), 0.06);\n    --border: 1px solid rgba(var(--default-color), 0.87);\n}\n.date-picker-left[data-v-7427c44c],\n.date-picker-notch[data-v-7427c44c],\n.date-picker-right[data-v-7427c44c] {\n    min-height: 100%;\n    max-height: 100%;\n    height: 100%;\n}\n.date-picker-left[data-v-7427c44c],\n.date-picker-right[data-v-7427c44c] {\n    min-height: var(--height);\n    max-height: var(--height);\n    padding-top: var(--padding-top);\n    padding-bottom: var(--padding-bottom);\n}\n.date-picker-notch[data-v-7427c44c] {\n    min-height: var(--height);\n    max-height: var(--height);\n    padding-top: 18px;\n    padding-bottom: 18px;\n}\n.date-picker-left[data-v-7427c44c] {\n    border-bottom: var(--border);\n    padding-left: calc(var(--horizontal-padding) - var(--label-padding));\n    border-top-left-radius: 4px;\n}\n.date-picker-outlined .date-picker-left[data-v-7427c44c] {\n    border: var(--border);\n    border-right: none;\n    border-bottom-left-radius: 4px;\n}\n.date-picker-notch[data-v-7427c44c] {\n    display: flex;\n    align-items: center;\n    border-bottom: var(--border);\n}\n.date-picker-outlined .date-picker-notch[data-v-7427c44c] {\n    border-top: var(--border);\n}\n.date-picker-right[data-v-7427c44c] {\n    border-bottom: var(--border);\n    width: 100%;\n    border-top-right-radius: 4px;\n}\n.date-picker-outlined .date-picker-right[data-v-7427c44c] {\n    border: var(--border);\n    border-left: none;\n    border-bottom-right-radius: 4px;\n}\n.date-picker input[data-v-7427c44c] {\n    flex: 1;\n    position: absolute;\n    padding: 0 var(--horizontal-padding);\n    padding-top: var(--padding-top);\n    padding-bottom: var(--padding-bottom);\n    outline: none;\n    border: none;\n    border-radius: 5px;\n    background: rgba(0, 0, 0, 0);\n    color: var(--default-color);\n    font-size: var(--font-size);\n    width: 100%;\n}\n.date-picker-label[data-v-7427c44c] {\n    flex: 1;\n    padding: 0 6px;\n    color: rgba(var(--default-color), 0.6);\n    transition-duration: 0.2s;\n    white-space: nowrap;\n    font-size: var(--font-size);\n}\n.date-picker-notch[data-v-7427c44c],\n.date-picker-outlined .date-picker-notch-filled[data-v-7427c44c] {\n    border-top: none;\n}\n.date-picker-filled .date-picker-notch .date-picker-label[data-v-7427c44c],\n.date-picker-filled .date-picker-label-filled[data-v-7427c44c] {\n    transform: translateY(calc((-1 / 4) * var(--height)));\n    font-size: 0.8rem;\n}\n.date-picker-outlined .date-picker-notch .date-picker-label[data-v-7427c44c],\n.date-picker-outlined .date-picker-label-filled[data-v-7427c44c] {\n    transform: translateY(calc((-1 / 2) * var(--height)));\n    font-size: 0.8rem;\n}\n.date-picker-filled input:focus ~ .date-picker-left[data-v-7427c44c],\n.date-picker-filled input:focus ~ .date-picker-notch[data-v-7427c44c],\n.date-picker-filled input:focus ~ .date-picker-right[data-v-7427c44c] {\n    background: rgba(var(--default-color), 0.1);\n}\ninput:focus ~ .date-picker-left[data-v-7427c44c],\ninput:focus ~ .date-picker-notch[data-v-7427c44c],\ninput:focus ~ .date-picker-right[data-v-7427c44c] {\n    border-color: #4c77d6;\n}\ninput:focus ~ .date-picker-notch .date-picker-label[data-v-7427c44c] {\n    color: #4c77d6;\n}\ninput[data-v-7427c44c]:-webkit-autofill,\ninput[data-v-7427c44c]:-webkit-autofill:hover,\ninput[data-v-7427c44c]:-webkit-autofill:focus,\ninput[data-v-7427c44c]:-webkit-autofill:active {\n    -webkit-background-clip: text;\n    -webkit-text-fill-color: rgb(var(--default-color));\n}\ninput[data-v-7427c44c]::-webkit-outer-spin-button,\ninput[data-v-7427c44c]::-webkit-inner-spin-button {\n    -webkit-appearance: none;\n    margin: 0;\n}\ninput[type='number'][data-v-7427c44c] {\n    -moz-appearance: textfield;\n}\n";
styleInject(css_248z$9);

script$8.render = render$8;
script$8.__scopeId = "data-v-7427c44c";

// vue
var script$9 = defineComponent({
  props: {
    spacing: {
      type: Number,
      default: 50
    }
  },

  // eslint-disable-next-line
  setup(props) {
    // computed
    const cssProperties = computed(() => {
      return `--spacing: ${props.spacing}px;`;
    });
    return {
      cssProperties
    };
  }

});

const _withId$9 = /*#__PURE__*/withScopeId("data-v-5c14f881");

const render$9 = /*#__PURE__*/_withId$9((_ctx, _cache, $props, $setup, $data, $options) => {
  return openBlock(), createBlock("div", {
    class: "divider",
    style: _ctx.cssProperties
  }, null, 4);
});

var css_248z$a = "\n.divider[data-v-5c14f881] {\n    width: 100%;\n    padding-bottom: var(--spacing);\n    margin-bottom: var(--spacing);\n    border-bottom: 1px solid rgba(var(--default-color), 0.1);\n}\n";
styleInject(css_248z$a);

script$9.render = render$9;
script$9.__scopeId = "data-v-5c14f881";

// vue
var script$a = defineComponent({
  props: {
    modelValue: {
      type: Number,
      default: 0
    },
    label: {
      type: String,
      default: 'Default'
    },
    placeholder: {
      type: String,
      default: 'Select an option'
    },
    options: {
      type: Array,
      default: () => {
        return [];
      }
    },
    value: {
      type: String,
      default: 'id'
    },
    title: {
      type: String,
      default: 'title'
    },
    sort: {
      type: Function,
      default: () => {
        return true;
      }
    }
  },

  // eslint-disable-next-line
  setup(props, context) {
    // refs
    const dropdownField = ref(null); // computed

    const isInputFilled = computed(() => {
      return props.modelValue > 0;
    });
    const sortedOptions = computed(() => {
      const tmpArray = [...props.options];
      return tmpArray.sort(props.sort);
    }); // methods

    const updateValue = e => {
      const val = parseInt(e.target.value);
      context.emit('update:modelValue', val);
    };

    return {
      props,
      sortedOptions,
      isInputFilled,
      dropdownField,
      updateValue
    };
  }

});

const _withId$a = /*#__PURE__*/withScopeId("data-v-524e0fcb");

pushScopeId("data-v-524e0fcb");

const _hoisted_1$7 = {
  class: "dropdown"
};
const _hoisted_2$3 = {
  class: "default-option-text",
  value: 0
};

const _hoisted_3$2 = /*#__PURE__*/createVNode("div", {
  class: "dropdown-left"
}, null, -1);

const _hoisted_4 = {
  class: "dropdown-notch"
};
const _hoisted_5 = {
  class: "dropdown-label"
};

const _hoisted_6 = /*#__PURE__*/createVNode("div", {
  class: "dropdown-right"
}, null, -1);

popScopeId();

const render$a = /*#__PURE__*/_withId$a((_ctx, _cache, $props, $setup, $data, $options) => {
  return openBlock(), createBlock("div", _hoisted_1$7, [createVNode("select", {
    class: ["dropdown-field", {
      'default-option': !_ctx.isInputFilled
    }],
    ref: "dropdownField",
    value: _ctx.modelValue,
    onInput: _cache[1] || (_cache[1] = (...args) => _ctx.updateValue && _ctx.updateValue(...args))
  }, [createVNode("option", _hoisted_2$3, toDisplayString(_ctx.props.placeholder), 1), (openBlock(true), createBlock(Fragment, null, renderList(_ctx.sortedOptions, (option, i) => {
    return openBlock(), createBlock("option", {
      key: i,
      value: option[_ctx.props.value]
    }, toDisplayString(option[_ctx.props.title]), 9, ["value"]);
  }), 128))], 42, ["value"]), _hoisted_3$2, createVNode("div", _hoisted_4, [createVNode("div", _hoisted_5, toDisplayString(_ctx.props.label), 1)]), _hoisted_6]);
});

var css_248z$b = "\n.dropdown[data-v-524e0fcb] {\n    --font-size: 1.1rem;\n    --label-padding: 6px;\n    --horizontal-padding: 16px;\n    --vertical-padding: 16px;\n    --border: 1px solid rgba(var(--default-color), 0.42);\n    --height: calc(var(--font-size) + (var(--vertical-padding) * 2) + 2px);\n    display: flex;\n    width: 100%;\n    font-size: 1.1rem;\n    border-radius: 4px;\n    background: rgba(255, 255, 255, 0);\n    position: relative;\n}\n.dropdown[data-v-524e0fcb]:hover {\n    --border: 1px solid rgba(var(--default-color), 0.87);\n}\n.dropdown-left[data-v-524e0fcb],\n.dropdown-notch[data-v-524e0fcb],\n.dropdown-right[data-v-524e0fcb] {\n    min-height: var(--height);\n    max-height: var(--height);\n    padding: var(--vertical-padding) 0;\n}\n.dropdown-left[data-v-524e0fcb] {\n    padding-left: calc(var(--horizontal-padding) - var(--label-padding));\n    border: var(--border);\n    border-right: none;\n    border-top-left-radius: 4px;\n    border-bottom-left-radius: 4px;\n}\n.dropdown-notch[data-v-524e0fcb] {\n    display: flex;\n    align-items: center;\n    padding: var(--vertical-padding) 0;\n    border-bottom: var(--border);\n}\n.dropdown-right[data-v-524e0fcb] {\n    width: 100%;\n    border: var(--border);\n    border-left: none;\n    border-top-right-radius: 4px;\n    border-bottom-right-radius: 4px;\n}\n.dropdown-field[data-v-524e0fcb] {\n    flex: 1;\n    width: 100%;\n    height: 100%;\n    position: absolute;\n    font-size: 1.1rem;\n    border: none;\n    padding: 0 calc(var(--horizontal-padding) - 4px);\n    background: rgba(255, 255, 255, 0);\n    color: rgb(var(--default-color));\n    margin-right: var(--horizontal-padding);\n    outline: none;\n}\n.dropdown-label[data-v-524e0fcb] {\n    flex: 1;\n    padding: 0 6px;\n    color: rgba(var(--default-color), 0.6);\n    transition-duration: 0.2s;\n    transform: translateY(calc((-1 / 2) * var(--height)));\n    font-size: 0.8rem;\n    white-space: nowrap;\n}\n.dropdown-field option[data-v-524e0fcb] {\n    color: rgba(0, 0, 0, 0.8);\n}\n.default-option[data-v-524e0fcb] {\n    color: rgba(var(--default-color), 0.6);\n}\n.dropdown-field option.default-option-text[data-v-524e0fcb] {\n    color: rgba(0, 0, 0, 0.6);\n}\n.dropdown-field:focus ~ .dropdown-left[data-v-524e0fcb],\n.dropdown-field:focus ~ .dropdown-notch[data-v-524e0fcb],\n.dropdown-field:focus ~ .dropdown-right[data-v-524e0fcb] {\n    border-color: #4c77d6;\n}\n.dropdown-field:focus ~ .dropdown-notch .dropdown-label[data-v-524e0fcb] {\n    color: #4c77d6;\n}\n";
styleInject(css_248z$b);

script$a.render = render$a;
script$a.__scopeId = "data-v-524e0fcb";

// vue
var script$b = defineComponent({
  props: {
    scale: {
      type: Number,
      default: 1
    },
    color: {
      type: String,
      default: 'rgb(145 201 82)'
    },
    ringWidth: {
      type: Number,
      default: 6
    },
    width: {
      type: Number,
      default: 80
    },
    height: {
      type: Number,
      default: 80
    }
  },

  // eslint-disable-next-line
  setup(props) {
    // refs
    const loadingSpinner = ref(null); // lifecycle hooks

    onMounted(() => {
      if (loadingSpinner.value) {
        loadingSpinner.value.style.setProperty('--spinner-scale', props.scale + '');
        loadingSpinner.value.style.setProperty('--spinner-color', props.color);
        loadingSpinner.value.style.setProperty('--ring-width', props.ringWidth * props.scale + 'px');
        loadingSpinner.value.style.setProperty('--width', props.width * props.scale + 'px');
        loadingSpinner.value.style.setProperty('--height', props.height * props.scale + 'px');
      }
    });
    return {
      loadingSpinner
    };
  }

});

const _withId$b = /*#__PURE__*/withScopeId("data-v-04827584");

pushScopeId("data-v-04827584");

const _hoisted_1$8 = {
  class: "loading-spinner-container"
};

const _hoisted_2$4 = /*#__PURE__*/createVNode("span", {
  class: "hidden-dash"
}, "-", -1);

const _hoisted_3$3 = {
  class: "loading-spinner",
  ref: "loadingSpinner"
};

const _hoisted_4$1 = /*#__PURE__*/createVNode("div", {
  class: "lds-dual-ring"
}, null, -1);

const _hoisted_5$1 = /*#__PURE__*/createVNode("span", {
  class: "hidden-dash"
}, "-", -1);

popScopeId();

const render$b = /*#__PURE__*/_withId$b((_ctx, _cache, $props, $setup, $data, $options) => {
  return openBlock(), createBlock("div", _hoisted_1$8, [_hoisted_2$4, createVNode("div", _hoisted_3$3, [_hoisted_4$1], 512), _hoisted_5$1]);
});

var css_248z$c = "\n.loading-spinner-container[data-v-04827584] {\n    display: flex;\n    align-items: center;\n}\n.loading-spinner[data-v-04827584] {\n    display: inline-flex;\n    justify-content: center;\n    align-items: center;\n    position: relative;\n    width: var(--width);\n    height: var(--height);\n    min-width: var(--width);\n    min-height: var(--height);\n    overflow: hidden;\n}\n.lds-dual-ring[data-v-04827584] {\n    display: inline-flex;\n    justify-content: center;\n    align-items: center;\n    position: relative;\n    width: var(--width);\n    height: var(--height);\n    min-width: var(--width);\n    min-height: var(--height);\n}\n.lds-dual-ring[data-v-04827584]:before {\n    content: '';\n    position: absolute;\n    top: 0;\n    left: 0;\n    right: 0;\n    bottom: 0;\n    width: 100%;\n    height: 100%;\n    border-radius: 50%;\n    border: var(--ring-width) solid rgba(255, 255, 255, 0.05);\n    box-sizing: border-box;\n}\n.lds-dual-ring[data-v-04827584]:after {\n    content: '';\n    position: absolute;\n    top: 0;\n    left: 0;\n    right: 0;\n    bottom: 0;\n    width: 100%;\n    height: 100%;\n    border-radius: 50%;\n    border: var(--ring-width) solid var(--spinner-color);\n    border-color: var(--spinner-color) transparent transparent transparent;\n    animation: lds-dual-ring-04827584 1.2s linear infinite;\n    box-sizing: border-box;\n}\n@keyframes lds-dual-ring-04827584 {\n0% {\n        transform: rotate(0deg);\n}\n100% {\n        transform: rotate(360deg);\n}\n}\n.hidden-dash[data-v-04827584] {\n    user-select: none;\n    color: rgba(0, 0, 0, 0);\n}\n";
styleInject(css_248z$c);

script$b.render = render$b;
script$b.__scopeId = "data-v-04827584";

// vue
var script$c = defineComponent({
  components: {
    VmLoadingSpinner: script$b
  },
  props: {
    config: {
      type: Object
    },
    rowData: {
      type: Array,
      default: () => {
        return [];
      }
    },
    editCallback: {
      type: Function
    },
    deleteCallback: {
      type: Function
    },
    isSelectable: {
      type: Boolean,
      default: false
    },
    isGridBorder: {
      type: Boolean,
      default: true
    },
    isLoading: {
      type: Boolean,
      default: false
    },
    maxRows: {
      type: Number,
      default: 10
    }
  },

  // eslint-disable-next-line
  setup(props) {
    // properties
    const selectedRowIds = ref([]);
    const currentPage = ref(1);
    const rowsPerPage = computed(() => {
      return props.maxRows ? props.maxRows : 10;
    }); // computed

    const totalPages = computed(() => {
      return Math.ceil(props.rowData.length / rowsPerPage.value);
    });
    const startRowIndex = computed(() => {
      return Math.ceil((currentPage.value - 1) * rowsPerPage.value);
    });
    const endRowIndex = computed(() => {
      let endRow = Math.ceil(currentPage.value * rowsPerPage.value - 1);

      if (endRow > props.rowData.length) {
        endRow = props.rowData.length - 1;
      }

      return endRow;
    });
    const emptyRows = computed(() => {
      if (props.isLoading) {
        return 0;
      }

      let assign = 0;

      if (currentPage.value === totalPages.value && props.rowData.length % rowsPerPage.value !== 0) {
        assign = rowsPerPage.value - Math.floor(props.rowData.length % rowsPerPage.value);
      }

      return assign;
    }); // methods

    const selectRowById = id => {
      if (props.isSelectable) {
        const index = selectedRowIds.value.indexOf(id);

        if (index !== -1) {
          selectedRowIds.value.splice(index, 1);
        } else {
          selectedRowIds.value.push(id);
        }
      }
    };

    const getSelectedIds = () => {
      return selectedRowIds.value;
    }; // eslint-disable-next-line


    const getFilteredRows = () => {
      if (props.isLoading) {
        return [];
      }

      const tmpRows = [...props.rowData];
      return tmpRows.splice(startRowIndex.value, rowsPerPage.value);
    };

    const setCurrentPage = pageIndex => {
      if (pageIndex > 0 && pageIndex <= totalPages.value) {
        currentPage.value = pageIndex;
      }
    };

    const incrementCurrentPage = () => {
      setCurrentPage(currentPage.value + 1);
    };

    const decrementCurrentPage = () => {
      setCurrentPage(currentPage.value - 1);
    };

    const goToLastPage = () => {
      setCurrentPage(totalPages.value);
    };

    const goToFirstPage = () => {
      setCurrentPage(1);
    };

    const deselectAll = () => {
      selectedRowIds.value = [];
    };

    return {
      props,
      selectRowById,
      selectedRowIds,
      getSelectedIds,
      rowsPerPage,
      currentPage,
      totalPages,
      startRowIndex,
      endRowIndex,
      emptyRows,
      getFilteredRows,
      incrementCurrentPage,
      decrementCurrentPage,
      goToLastPage,
      goToFirstPage,
      deselectAll
    };
  }

});

const _withId$c = /*#__PURE__*/withScopeId("data-v-5a3bb4df");

pushScopeId("data-v-5a3bb4df");

const _hoisted_1$9 = {
  class: "grid-wrapper"
};
const _hoisted_2$5 = {
  key: 0,
  style: {
    "width": "1px"
  }
};
const _hoisted_3$4 = {
  key: 0,
  class: "loading-row"
};
const _hoisted_4$2 = {
  colspan: "10000"
};
const _hoisted_5$2 = {
  class: "row jc-center"
};
const _hoisted_6$1 = {
  key: 1
};

const _hoisted_7 = /*#__PURE__*/createVNode("td", {
  class: "center-text",
  colspan: "10000"
}, " No data available ", -1);

const _hoisted_8 = {
  key: 0
};
const _hoisted_9 = {
  key: 0
};

const _hoisted_10 = /*#__PURE__*/createVNode("td", null, ".", -1);

const _hoisted_11 = {
  class: "pagination"
};

const _hoisted_12 = /*#__PURE__*/createVNode("div", {
  class: "row-counter"
}, null, -1);

const _hoisted_13 = {
  class: "page-controls"
};
const _hoisted_14 = {
  class: "current-page"
};

const _hoisted_15 = /*#__PURE__*/createVNode("i", {
  class: "far fa-chevron-double-left"
}, null, -1);

const _hoisted_16 = /*#__PURE__*/createVNode("i", {
  class: "far fa-chevron-left"
}, null, -1);

const _hoisted_17 = /*#__PURE__*/createVNode("i", {
  class: "far fa-chevron-right"
}, null, -1);

const _hoisted_18 = /*#__PURE__*/createVNode("i", {
  class: "far fa-chevron-double-right"
}, null, -1);

popScopeId();

const render$c = /*#__PURE__*/_withId$c((_ctx, _cache, $props, $setup, $data, $options) => {
  const _component_vm_loading_spinner = resolveComponent("vm-loading-spinner");

  return openBlock(), createBlock("div", {
    class: ["grid-container", {
      'grid-border': _ctx.isGridBorder
    }]
  }, [createVNode("div", _hoisted_1$9, [createVNode("table", {
    class: ["grid", {
      'is-selectable': _ctx.props.isSelectable
    }]
  }, [createVNode("thead", null, [createVNode("tr", null, [_ctx.isSelectable ? (openBlock(), createBlock("th", _hoisted_2$5)) : createCommentVNode("", true), (openBlock(true), createBlock(Fragment, null, renderList(_ctx.config, (headerConfig, i) => {
    return openBlock(), createBlock("th", {
      key: i
    }, toDisplayString(headerConfig.title), 1);
  }), 128))])]), createVNode("tbody", null, [_ctx.isLoading ? (openBlock(), createBlock("tr", _hoisted_3$4, [createVNode("td", _hoisted_4$2, [createVNode("div", _hoisted_5$2, [createVNode(_component_vm_loading_spinner, {
    scale: 0.8
  }, null, 8, ["scale"])])])])) : createCommentVNode("", true), _ctx.rowData.length === 0 && !_ctx.isLoading ? (openBlock(), createBlock("tr", _hoisted_6$1, [_hoisted_7])) : createCommentVNode("", true), (openBlock(true), createBlock(Fragment, null, renderList(_ctx.getFilteredRows(), (row, i) => {
    return openBlock(), createBlock("tr", {
      class: {
        selected: _ctx.selectedRowIds.indexOf(row.id) !== -1
      },
      key: i
    }, [_ctx.isSelectable ? (openBlock(), createBlock("td", _hoisted_8, [createVNode("input", {
      type: "checkbox",
      checked: _ctx.selectedRowIds.find(x => x === row.id),
      onChange: $event => _ctx.selectRowById(row.id)
    }, null, 40, ["checked", "onChange"])])) : createCommentVNode("", true), (openBlock(true), createBlock(Fragment, null, renderList(_ctx.config, (headerConfig, i) => {
      return openBlock(), createBlock("td", {
        class: {
          'fit-width': headerConfig.fitWidth
        },
        key: i
      }, [!headerConfig.cellRenderer ? (openBlock(), createBlock("div", _hoisted_9, toDisplayString(row[headerConfig.field]), 1)) : createCommentVNode("", true), headerConfig.cellRenderer ? (openBlock(), createBlock(resolveDynamicComponent(headerConfig.cellRenderer.component), {
        key: 1,
        row: row,
        field: headerConfig.field,
        exported: headerConfig.cellRenderer.exported
      }, null, 8, ["row", "field", "exported"])) : createCommentVNode("", true)], 2);
    }), 128))], 2);
  }), 128)), (openBlock(true), createBlock(Fragment, null, renderList(_ctx.emptyRows, i => {
    return openBlock(), createBlock("tr", {
      class: "empty-row",
      key: i
    }, [_hoisted_10]);
  }), 128))])], 2)]), createVNode("div", _hoisted_11, [createVNode("div", {
    class: ["pagination-wrapper", {
      'pagination-hidden': _ctx.isLoading
    }]
  }, [_hoisted_12, createVNode("div", _hoisted_13, [createVNode("div", _hoisted_14, toDisplayString(_ctx.rowData.length ? _ctx.startRowIndex + 1 : 0) + "-" + toDisplayString(_ctx.endRowIndex + 1) + " of " + toDisplayString(_ctx.rowData.length), 1), createVNode("div", {
    class: "page-control-button",
    onClick: _cache[1] || (_cache[1] = (...args) => _ctx.goToFirstPage && _ctx.goToFirstPage(...args))
  }, [_hoisted_15]), createVNode("div", {
    class: "page-control-button",
    onClick: _cache[2] || (_cache[2] = (...args) => _ctx.decrementCurrentPage && _ctx.decrementCurrentPage(...args))
  }, [_hoisted_16]), createVNode("div", {
    class: "page-control-button",
    onClick: _cache[3] || (_cache[3] = (...args) => _ctx.incrementCurrentPage && _ctx.incrementCurrentPage(...args))
  }, [_hoisted_17]), createVNode("div", {
    class: "page-control-button",
    onClick: _cache[4] || (_cache[4] = (...args) => _ctx.goToLastPage && _ctx.goToLastPage(...args))
  }, [_hoisted_18])])], 2)])], 2);
});

var css_248z$d = "\n.grid-container[data-v-5a3bb4df] {\n    --grid-border-color: rgba(var(--default-color), 0.3);\n    overflow: hidden;\n    font-size: 0.9rem;\n    flex: 1;\n}\n.grid-wrapper[data-v-5a3bb4df] {\n    overflow-x: auto;\n}\n.grid[data-v-5a3bb4df] {\n    border-collapse: collapse;\n    margin: 0 auto;\n    overflow: hidden;\n    overflow-x: scroll;\n    width: 100%;\n}\n.grid thead tr[data-v-5a3bb4df] {\n    color: rgb(var(--default-color));\n    text-align: left;\n    border-bottom: 1px solid var(--grid-border-color);\n}\n.grid thead tr th[data-v-5a3bb4df] {\n    font-weight: 600;\n}\n.grid th[data-v-5a3bb4df] {\n    white-space: nowrap;\n    padding: 0 16px;\n    height: 50px;\n}\n.grid td[data-v-5a3bb4df] {\n    height: 46px;\n    white-space: nowrap;\n}\n.grid td[data-v-5a3bb4df] {\n    padding: 0 16px;\n}\n.grid tbody tr[data-v-5a3bb4df] {\n    border-bottom: 1px solid rgba(var(--default-color), 0.1);\n    transition-duration: 0.1s;\n}\n.grid tbody tr[data-v-5a3bb4df]:last-child {\n    border-bottom: none;\n}\n.grid tbody tr[data-v-5a3bb4df]:hover {\n    background: rgba(var(--default-color), 0.02);\n    transition-duration: 0.1s;\n}\n.grid.is-selectable tbody tr.selected[data-v-5a3bb4df] {\n    background: #d5eaf8;\n}\n.grid tbody tr.empty-row[data-v-5a3bb4df] {\n    visibility: hidden;\n    border-bottom: 1px solid rgba(0, 0, 0, 0);\n}\n.grid tbody tr.empty-row[data-v-5a3bb4df]:last-child {\n    border-bottom: none;\n}\n.grid tbody tr td.fit-width[data-v-5a3bb4df] {\n    width: 1px;\n    white-space: nowrap;\n}\n.pagination-hidden[data-v-5a3bb4df] {\n    visibility: hidden;\n    pointer-events: none;\n    user-select: none;\n}\n.pagination[data-v-5a3bb4df] {\n    padding: 10px 10px;\n    color: rgb(var(--default-color));\n    border-top: 1px solid var(--grid-border-color);\n}\n.pagination-wrapper[data-v-5a3bb4df] {\n    display: flex;\n    justify-content: space-between;\n}\n.pagination > .pagination-wrapper > .page-controls[data-v-5a3bb4df] {\n    display: flex;\n}\n.pagination > .pagination-wrapper > .page-controls > .current-page[data-v-5a3bb4df] {\n    padding: 0 10px;\n}\n.pagination > .pagination-wrapper > .page-controls > .page-control-button[data-v-5a3bb4df] {\n    color: rgb(var(--default-color), 0.4);\n    padding: 0 3px;\n    margin: 0 10px;\n    cursor: pointer;\n    transition-duration: 0.1s;\n}\n.pagination\n    > .pagination-wrapper\n    > .page-controls\n    > .page-control-button[data-v-5a3bb4df]:hover {\n    color: rgb(var(--default-color), 0.8);\n    transition-duration: 0.1s;\n}\n.grid tbody tr.loading-row[data-v-5a3bb4df] {\n    border-bottom: none;\n}\n.grid tbody tr.loading-row td[data-v-5a3bb4df] {\n    padding: 10px 0;\n}\n";
styleInject(css_248z$d);

script$c.render = render$c;
script$c.__scopeId = "data-v-5a3bb4df";

// vue
var script$d = defineComponent({
  components: {
    VmActionButton: script$1,
    VmCard: script$4
  },
  props: {
    title: {
      type: String,
      default: ''
    },
    isPreventClose: {
      type: Boolean,
      default: false
    }
  },

  // eslint-disable-next-line
  setup(props) {
    // properties
    const isVisible = ref(false); // methods

    const show = () => {
      isVisible.value = true;
    };

    const hide = () => {
      if (!props.isPreventClose) {
        isVisible.value = false;
      }
    };

    return {
      props,
      isVisible,
      show,
      hide
    };
  }

});

const _withId$d = /*#__PURE__*/withScopeId("data-v-1b5e3877");

pushScopeId("data-v-1b5e3877");

const _hoisted_1$a = {
  class: "modal"
};
const _hoisted_2$6 = {
  class: "modal-content"
};

popScopeId();

const render$d = /*#__PURE__*/_withId$d((_ctx, _cache, $props, $setup, $data, $options) => {
  const _component_vm_card = resolveComponent("vm-card");

  return openBlock(), createBlock(Teleport, {
    to: "#app-theme"
  }, [createVNode("div", _hoisted_1$a, [createVNode(_component_vm_card, {
    class: ["modal-pane", {
      'modal-visible': _ctx.isVisible
    }],
    elevation: 8
  }, {
    default: _withId$d(() => [createVNode("div", _hoisted_2$6, [renderSlot(_ctx.$slots, "default")])]),
    _: 3
  }, 8, ["class"]), createVNode("div", {
    class: ["modal-background", {
      'modal-background-visible': _ctx.isVisible
    }],
    onClick: _cache[1] || (_cache[1] = $event => _ctx.hide())
  }, null, 2)])]);
});

var css_248z$e = "\n.modal[data-v-1b5e3877] {\n    position: fixed;\n    top: 0;\n    bottom: 0;\n    left: 0;\n    right: 0;\n    display: flex;\n    justify-content: center;\n    align-items: center;\n    z-index: 999;\n    pointer-events: none;\n}\n.modal-content[data-v-1b5e3877] {\n    height: 100%;\n    overflow-y: auto;\n}\n.modal-pane[data-v-1b5e3877] {\n    position: relative;\n    z-index: 1000;\n    overflow: hidden;\n    border-radius: 3px;\n    width: 600px;\n    max-width: 600px;\n    margin: 30px;\n    display: flex;\n    flex-direction: column;\n    max-height: 90%;\n    opacity: 0;\n    pointer-events: none;\n    z-index: 2;\n    transform: scale(0.8);\n    transition: transform 0.2s, opacity 0.2s;\n}\n.modal-background[data-v-1b5e3877] {\n    opacity: 0;\n    pointer-events: none;\n    position: fixed;\n    background: rgba(0, 0, 0, 0.7);\n    top: 0;\n    bottom: 0;\n    left: 0;\n    right: 0;\n    z-index: 1;\n    transition: opacity 0.2s;\n}\n.modal-visible[data-v-1b5e3877] {\n    transform: scale(1);\n}\n.modal-background-visible[data-v-1b5e3877],\n.modal-visible[data-v-1b5e3877] {\n    opacity: 1;\n    pointer-events: all;\n    transition: transform 0.2s, opacity 0.2s;\n}\n";
styleInject(css_248z$e);

script$d.render = render$d;
script$d.__scopeId = "data-v-1b5e3877";

// vue
var script$e = defineComponent({
  props: {
    elevation: {
      type: Number,
      default: 2
    }
  },

  setup(props) {
    return {
      props
    };
  }

});

const _withId$e = /*#__PURE__*/withScopeId("data-v-2b1176fa");

const render$e = /*#__PURE__*/_withId$e((_ctx, _cache, $props, $setup, $data, $options) => {
  return openBlock(), createBlock("div", {
    class: ["navigation-drawer", [`elevation-${_ctx.props.elevation}`, `elevation-background-${_ctx.props.elevation}`]]
  }, [renderSlot(_ctx.$slots, "default")], 2);
});

var css_248z$f = "\n.navigation-drawer[data-v-2b1176fa] {\n    padding: 20px 32px;\n    overflow-y: auto;\n    transition: box-shadow 0.2s;\n}\n.navigation-drawer[data-v-2b1176fa]:before {\n    transition: background 0.2s;\n}\n";
styleInject(css_248z$f);

script$e.render = render$e;
script$e.__scopeId = "data-v-2b1176fa";

// vue
var script$f = defineComponent({
  props: {
    modelValue: {
      type: Number,
      default: 0,
      required: true
    },
    label: {
      type: String,
      default: 'Default'
    }
  },

  // eslint-disable-next-line
  setup(props, context) {
    // refs
    const textField = ref(null); // computed

    const isInputFilled = computed(() => {
      return (props.modelValue + '').length > 0;
    }); // methods

    const updateValue = e => {
      const val = e.target.value;

      try {
        parseInt(val);
        context.emit('update:modelValue', val);
      } catch (e) {
        window.console.log(e);
      }
    };

    return {
      props,
      isInputFilled,
      textField,
      updateValue
    };
  }

});

const _withId$f = /*#__PURE__*/withScopeId("data-v-38e24d5a");

pushScopeId("data-v-38e24d5a");

const _hoisted_1$b = {
  class: "text-field"
};

const _hoisted_2$7 = /*#__PURE__*/createVNode("div", {
  class: "text-field-left"
}, null, -1);

const _hoisted_3$5 = /*#__PURE__*/createVNode("div", {
  class: "text-field-right"
}, null, -1);

popScopeId();

const render$f = /*#__PURE__*/_withId$f((_ctx, _cache, $props, $setup, $data, $options) => {
  return openBlock(), createBlock("div", _hoisted_1$b, [createVNode("input", {
    class: "text-field-input",
    type: "text",
    ref: "textField",
    value: _ctx.modelValue,
    onInput: _cache[1] || (_cache[1] = (...args) => _ctx.updateValue && _ctx.updateValue(...args))
  }, null, 40, ["value"]), _hoisted_2$7, createVNode("div", {
    class: ["text-field-notch", {
      'text-field-notch-filled': _ctx.isInputFilled
    }]
  }, [createVNode("div", {
    class: ["text-field-label", {
      'text-field-label-filled': _ctx.isInputFilled
    }]
  }, toDisplayString(_ctx.props.label), 3)], 2), _hoisted_3$5]);
});

var css_248z$g = "\n.text-field[data-v-38e24d5a] {\n    --font-size: 1.1rem;\n    --label-padding: 6px;\n    --horizontal-padding: 16px;\n    --vertical-padding: 16px;\n    --border: 1px solid rgba(255, 255, 255, 0.3);\n    --height: calc(var(--font-size) + (var(--vertical-padding) * 2) + 2px);\n    display: flex;\n    align-items: center;\n    width: 100%;\n    position: relative;\n}\n.text-field-left[data-v-38e24d5a],\n.text-field-notch[data-v-38e24d5a],\n.text-field-right[data-v-38e24d5a] {\n    min-height: var(--height);\n    max-height: var(--height);\n    padding: var(--vertical-padding) 0;\n}\n.text-field-left[data-v-38e24d5a] {\n    padding-left: calc(var(--horizontal-padding) - var(--label-padding));\n    border: var(--border);\n    border-right: none;\n    border-top-left-radius: 4px;\n    border-bottom-left-radius: 4px;\n}\n.text-field-notch[data-v-38e24d5a] {\n    display: flex;\n    align-items: center;\n    padding: var(--vertical-padding) 0;\n    border-top: var(--border);\n    border-bottom: var(--border);\n}\n.text-field-right[data-v-38e24d5a] {\n    width: 100%;\n    border: var(--border);\n    border-left: none;\n    border-top-right-radius: 4px;\n    border-bottom-right-radius: 4px;\n}\n.text-field input[data-v-38e24d5a] {\n    flex: 1;\n    position: absolute;\n    padding: var(--vertical-padding) var(--horizontal-padding);\n    outline: none;\n    border: none;\n    border-radius: 5px;\n    background: rgba(0, 0, 0, 0);\n    color: #fff;\n    font-size: var(--font-size);\n    width: 100%;\n}\n.text-field-label[data-v-38e24d5a] {\n    flex: 1;\n    padding: 0 6px;\n    color: #aaa;\n    transition-duration: 0.2s;\n    white-space: nowrap;\n    font-size: var(--font-size);\n}\ninput:focus ~ .text-field-notch[data-v-38e24d5a],\n.text-field-notch-filled[data-v-38e24d5a] {\n    border-top: none;\n}\ninput:focus ~ .text-field-notch .text-field-label[data-v-38e24d5a],\n.text-field-label-filled[data-v-38e24d5a] {\n    transform: translateY(calc((-1 / 2) * var(--height)));\n    font-size: 0.8rem;\n}\ninput:focus ~ .text-field-left[data-v-38e24d5a],\ninput:focus ~ .text-field-notch[data-v-38e24d5a],\ninput:focus ~ .text-field-right[data-v-38e24d5a] {\n    border-color: #2c60d1;\n}\ninput:focus ~ .text-field-notch .text-field-label[data-v-38e24d5a] {\n    color: #82a4ed;\n}\n";
styleInject(css_248z$g);

script$f.render = render$f;
script$f.__scopeId = "data-v-38e24d5a";

// vue
var script$g = defineComponent({
  setup() {
    return {};
  }

});

const _withId$g = /*#__PURE__*/withScopeId("data-v-26a7c9a2");

pushScopeId("data-v-26a7c9a2");

const _hoisted_1$c = {
  class: "page-header"
};

popScopeId();

const render$g = /*#__PURE__*/_withId$g((_ctx, _cache, $props, $setup, $data, $options) => {
  return openBlock(), createBlock("div", _hoisted_1$c, [renderSlot(_ctx.$slots, "default")]);
});

var css_248z$h = "\n.page-header[data-v-26a7c9a2] {\n    font-size: 2.2rem;\n    margin-top: calc(var(--default-margin) * 4);\n    margin-bottom: calc(var(--default-margin) * 6);\n}\n";
styleInject(css_248z$h);

script$g.render = render$g;
script$g.__scopeId = "data-v-26a7c9a2";

var script$h = {
  props: {
    id: {
      type: Number
    },
    modelValue: {
      type: Boolean,
      default: false
    },
    label: {
      type: String
    }
  },

  // eslint-disable-next-line
  setup(props, context) {
    // methods
    // eslint-disable-next-line
    const updateValue = e => {
      context.emit('update:modelValue', e.target.checked);
    };

    return {
      props,
      updateValue
    };
  }

};

const _withId$h = /*#__PURE__*/withScopeId("data-v-5779ef6a");

pushScopeId("data-v-5779ef6a");

const _hoisted_1$d = {
  class: "switch-container"
};
const _hoisted_2$8 = {
  class: "switch"
};

const _hoisted_3$6 = /*#__PURE__*/createVNode("span", {
  class: "slider"
}, null, -1);

const _hoisted_4$3 = {
  key: 0,
  class: "label"
};

popScopeId();

const render$h = /*#__PURE__*/_withId$h((_ctx, _cache, $props, $setup, $data, $options) => {
  return openBlock(), createBlock("div", _hoisted_1$d, [createVNode("label", _hoisted_2$8, [createVNode("input", {
    id: $props.id,
    type: "checkbox",
    checked: $props.modelValue,
    onInput: _cache[1] || (_cache[1] = (...args) => $setup.updateValue && $setup.updateValue(...args))
  }, null, 40, ["id", "checked"]), _hoisted_3$6]), $setup.props.label ? (openBlock(), createBlock("label", _hoisted_4$3, toDisplayString($setup.props.label), 1)) : createCommentVNode("", true)]);
});

var css_248z$i = "\n.switch-container[data-v-5779ef6a] {\n    display: flex;\n    align-items: center;\n}\n.switch[data-v-5779ef6a] {\n    --width: 34px;\n    --switch-size: 20px;\n    --height: 14px;\n    --border-radius: 100px;\n    --switch-border-radius: 50%;\n    --inner-padding: -1px;\n\n    width: var(--width);\n    min-width: var(--width);\n    height: var(--height);\n    cursor: pointer;\n    background-color: #adadad;\n    border-radius: var(--border-radius);\n    position: relative;\n    display: inline-block;\n}\n.switch input[data-v-5779ef6a] {\n    display: none;\n}\n.slider[data-v-5779ef6a] {\n    position: absolute;\n    border-radius: inherit;\n    top: 0;\n    left: 0;\n    right: 0;\n    bottom: 0;\n    transition: 0.4s;\n    z-index: 1;\n}\n.slider[data-v-5779ef6a]:before {\n    display: flex;\n    justify-content: center;\n    position: absolute;\n    border-radius: var(--switch-border-radius);\n    content: '';\n    width: var(--switch-size);\n    height: var(--switch-size);\n    left: var(--inner-padding);\n    top: calc((var(--height) / 2) - (var(--switch-size) / 2));\n    background-color: #fff;\n    transition: 0.3s;\n    box-shadow: var(--elevation-2);\n}\ninput:checked + .slider[data-v-5779ef6a] {\n    background: rgb(59, 169, 53);\n    border-radius: inherit;\n}\n.slider[data-v-5779ef6a]:after {\n    --padding: 12px;\n    content: '';\n    background: rgb(177, 127, 247, 0);\n    border-radius: 200px;\n    display: flex;\n    width: var(--switch-size);\n    height: var(--switch-size);\n    left: calc(var(--inner-padding) - var(--padding));\n    top: calc((var(--height) / 2) - (var(--switch-size) / 2) - var(--padding));\n    position: absolute;\n    padding: var(--padding);\n    pointer-events: none;\n    z-index: -1;\n    transition: background 0.2s, transform 0.3s;\n}\ninput:hover + .slider[data-v-5779ef6a]:after {\n    background: rgb(173, 173, 173, 0.2);\n}\ninput:checked + .slider[data-v-5779ef6a]:after {\n    transform: translateX(\n        calc(var(--width) - var(--switch-size) - (2 * var(--inner-padding)))\n    );\n}\ninput:checked:hover + .slider[data-v-5779ef6a]:after {\n    background: rgba(101, 214, 94, 0.2);\n}\ninput:checked ~ .slider[data-v-5779ef6a]:before {\n    background: rgb(101, 214, 94);\n}\ninput:focus + .slider[data-v-5779ef6a] {\n}\ninput:checked + .slider[data-v-5779ef6a]:before {\n    transform: translateX(\n        calc(var(--width) - var(--switch-size) - (2 * var(--inner-padding)))\n    );\n}\n.label[data-v-5779ef6a] {\n    margin-left: calc(var(--default-margin) * 3);\n}\n";
styleInject(css_248z$i);

script$h.render = render$h;
script$h.__scopeId = "data-v-5779ef6a";

// vue
var script$i = defineComponent({
  props: {
    modelValue: {
      type: [String, Number]
    },
    variant: {
      type: String,
      validator: prop => ['filled', 'outlined'].includes(prop),
      default: 'filled'
    },
    label: {
      type: String,
      default: 'Default'
    },
    type: {
      type: String,
      validator: prop => ['text', 'number', 'password'].includes(prop),
      default: 'text'
    }
  },

  // eslint-disable-next-line
  setup(props, context) {
    // refs
    const textField = ref(null); // computed

    const isInputFilled = computed(() => {
      if (props.modelValue === undefined) {
        return false;
      }

      if (props.type === 'number') {
        return props.modelValue !== NaN;
      }

      return props.modelValue.length > 0;
    }); // methods

    const updateValue = e => {
      const val = e.target.value;

      if (val !== undefined) {
        if (props.type === 'number') {
          const numberVal = val !== '' ? parseFloat(val) : undefined;
          context.emit('update:modelValue', numberVal);
        } else {
          context.emit('update:modelValue', val);
        }
      }
    };

    return {
      props,
      isInputFilled,
      textField,
      updateValue
    };
  }

});

const _withId$i = /*#__PURE__*/withScopeId("data-v-4c24feac");

pushScopeId("data-v-4c24feac");

const _hoisted_1$e = /*#__PURE__*/createVNode("div", {
  class: "text-field-left"
}, null, -1);

const _hoisted_2$9 = /*#__PURE__*/createVNode("div", {
  class: "text-field-right"
}, null, -1);

popScopeId();

const render$i = /*#__PURE__*/_withId$i((_ctx, _cache, $props, $setup, $data, $options) => {
  return openBlock(), createBlock("div", {
    class: ["text-field", 'text-field-' + _ctx.props.variant]
  }, [createVNode("input", {
    class: "text-field-input",
    type: _ctx.type,
    ref: "textField",
    value: _ctx.modelValue,
    onInput: _cache[1] || (_cache[1] = (...args) => _ctx.updateValue && _ctx.updateValue(...args))
  }, null, 40, ["type", "value"]), _hoisted_1$e, createVNode("div", {
    class: ["text-field-notch", {
      'text-field-notch-filled': _ctx.isInputFilled
    }]
  }, [createVNode("div", {
    class: ["text-field-label", {
      'text-field-label-filled': _ctx.isInputFilled
    }]
  }, toDisplayString(_ctx.props.label), 3)], 2), _hoisted_2$9], 2);
});

var css_248z$j = "\n.text-field[data-v-4c24feac] {\n    --font-size: 1rem;\n    --label-padding: 6px;\n    --horizontal-padding: 16px;\n    --border: 1px solid rgba(var(--default-color), 0.42);\n    --height: calc(\n        var(--font-size) + (var(--padding-top) + var(--padding-bottom)) + 2px\n    );\n    display: flex;\n    align-items: center;\n    width: 100%;\n    position: relative;\n}\n.text-field-filled[data-v-4c24feac] {\n    --padding-top: 24px;\n    --padding-bottom: calc(38px - var(--padding-top));\n    transition: background 0.2s;\n}\n.text-field-filled[data-v-4c24feac]:hover {\n    --padding-bottom: calc(38px - var(--padding-top));\n}\n.text-field-outlined[data-v-4c24feac] {\n    --padding-top: 18px;\n    --padding-bottom: 18px;\n}\n.text-field-outlined:hover .text-field-left[data-v-4c24feac],\n.text-field-outlined:hover .text-field-notch[data-v-4c24feac],\n.text-field-outlined:hover .text-field-right[data-v-4c24feac] {\n    --border: 1px solid rgba(var(--default-color), 0.87);\n}\n.text-field-filled .text-field-left[data-v-4c24feac],\n.text-field-filled .text-field-notch[data-v-4c24feac],\n.text-field-filled .text-field-right[data-v-4c24feac] {\n    background: rgba(var(--default-color), 0.03);\n}\n.text-field-filled:hover .text-field-left[data-v-4c24feac],\n.text-field-filled:hover .text-field-notch[data-v-4c24feac],\n.text-field-filled:hover .text-field-right[data-v-4c24feac] {\n    background: rgba(var(--default-color), 0.06);\n    --border: 1px solid rgba(var(--default-color), 0.87);\n}\n.text-field-left[data-v-4c24feac],\n.text-field-notch[data-v-4c24feac],\n.text-field-right[data-v-4c24feac] {\n    min-height: 100%;\n    max-height: 100%;\n    height: 100%;\n}\n.text-field-left[data-v-4c24feac],\n.text-field-right[data-v-4c24feac] {\n    min-height: var(--height);\n    max-height: var(--height);\n    padding-top: var(--padding-top);\n    padding-bottom: var(--padding-bottom);\n}\n.text-field-notch[data-v-4c24feac] {\n    min-height: var(--height);\n    max-height: var(--height);\n    padding-top: 18px;\n    padding-bottom: 18px;\n}\n.text-field-left[data-v-4c24feac] {\n    border-bottom: var(--border);\n    padding-left: calc(var(--horizontal-padding) - var(--label-padding));\n    border-top-left-radius: 4px;\n}\n.text-field-outlined .text-field-left[data-v-4c24feac] {\n    border: var(--border);\n    border-right: none;\n    border-bottom-left-radius: 4px;\n}\n.text-field-notch[data-v-4c24feac] {\n    display: flex;\n    align-items: center;\n    border-bottom: var(--border);\n}\n.text-field-outlined .text-field-notch[data-v-4c24feac] {\n    border-top: var(--border);\n}\n.text-field-right[data-v-4c24feac] {\n    border-bottom: var(--border);\n    width: 100%;\n    border-top-right-radius: 4px;\n}\n.text-field-outlined .text-field-right[data-v-4c24feac] {\n    border: var(--border);\n    border-left: none;\n    border-bottom-right-radius: 4px;\n}\n.text-field input[data-v-4c24feac] {\n    flex: 1;\n    position: absolute;\n    padding: 0 var(--horizontal-padding);\n    padding-top: var(--padding-top);\n    padding-bottom: var(--padding-bottom);\n    outline: none;\n    border: none;\n    border-radius: 5px;\n    background: rgba(0, 0, 0, 0);\n    color: var(--default-color);\n    font-size: var(--font-size);\n    width: 100%;\n}\n.text-field-label[data-v-4c24feac] {\n    flex: 1;\n    padding: 0 6px;\n    color: rgba(var(--default-color), 0.6);\n    transition-duration: 0.2s;\n    white-space: nowrap;\n    font-size: var(--font-size);\n}\n.text-field-outlined input:focus ~ .text-field-notch[data-v-4c24feac],\n.text-field-outlined .text-field-notch-filled[data-v-4c24feac] {\n    border-top: none;\n}\n.text-field-filled input:focus ~ .text-field-notch .text-field-label[data-v-4c24feac],\n.text-field-filled .text-field-label-filled[data-v-4c24feac] {\n    transform: translateY(calc((-1 / 4) * var(--height)));\n    font-size: 0.8rem;\n}\n.text-field-outlined input:focus ~ .text-field-notch .text-field-label[data-v-4c24feac],\n.text-field-outlined .text-field-label-filled[data-v-4c24feac] {\n    transform: translateY(calc((-1 / 2) * var(--height)));\n    font-size: 0.8rem;\n}\n.text-field-filled input:focus ~ .text-field-left[data-v-4c24feac],\n.text-field-filled input:focus ~ .text-field-notch[data-v-4c24feac],\n.text-field-filled input:focus ~ .text-field-right[data-v-4c24feac] {\n    background: rgba(var(--default-color), 0.1);\n}\ninput:focus ~ .text-field-left[data-v-4c24feac],\ninput:focus ~ .text-field-notch[data-v-4c24feac],\ninput:focus ~ .text-field-right[data-v-4c24feac] {\n    border-color: #4c77d6;\n}\ninput:focus ~ .text-field-notch .text-field-label[data-v-4c24feac] {\n    color: #4c77d6;\n}\ninput[data-v-4c24feac]:-webkit-autofill,\ninput[data-v-4c24feac]:-webkit-autofill:hover,\ninput[data-v-4c24feac]:-webkit-autofill:focus,\ninput[data-v-4c24feac]:-webkit-autofill:active {\n    -webkit-background-clip: text;\n    -webkit-text-fill-color: rgb(var(--default-color));\n}\ninput[data-v-4c24feac]::-webkit-outer-spin-button,\ninput[data-v-4c24feac]::-webkit-inner-spin-button {\n    -webkit-appearance: none;\n    margin: 0;\n}\ninput[type='number'][data-v-4c24feac] {\n    -moz-appearance: textfield;\n}\n";
styleInject(css_248z$j);

script$i.render = render$i;
script$i.__scopeId = "data-v-4c24feac";

var script$j = {
  props: {
    mode: {
      type: String,
      default: 'light'
    }
  },

  // eslint-disable-next-line
  setup(props) {
    return {
      props
    };
  }

};

function render$j(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock("div", {
    id: "app-theme",
    class: ["theme", $setup.props.mode + '-theme'],
    ref: "theme"
  }, [renderSlot(_ctx.$slots, "default")], 2);
}

var css_248z$k = "\n@import url('https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&display=swap');\n* {\n    box-sizing: border-box;\n}\nhtml {\n    display: flex;\n    width: 100%;\n    min-height: 100%;\n}\nbody {\n    display: flex;\n    width: 100%;\n    min-height: 100vh;\n    flex: 1;\n    margin: 0;\n}\n#app {\n    display: flex;\n    width: 100%;\n    height: 100%;\n    flex: 1;\n}\n.theme {\n    --default-margin: 5px;\n    width: 100%;\n    height: 100%;\n    flex: 1;\n    font-family: 'Roboto', sans-serif;\n    background: rgb(var(--default-background));\n    color: rgb(var(--default-color));\n\n    --baseline-color: 0, 0, 0;\n    --umbra-opacity: 0.2;\n    --penumbra-opacity: 0.14;\n    --ambient-opacity: 0.12;\n\n    --umbra-map-0: 0px 0px 0px 0px;\n    --umbra-map-1: 0px 2px 1px -1px;\n    --umbra-map-2: 0px 3px 1px -2px;\n    --umbra-map-3: 0px 3px 3px -2px;\n    --umbra-map-4: 0px 2px 4px -1px;\n    --umbra-map-5: 0px 3px 5px -1px;\n    --umbra-map-6: 0px 3px 5px -1px;\n    --umbra-map-7: 0px 4px 5px -2px;\n    --umbra-map-8: 0px 5px 5px -3px;\n    --umbra-map-9: 0px 5px 6px -3px;\n    --umbra-map-10: 0px 6px 6px -3px;\n    --umbra-map-11: 0px 6px 7px -4px;\n    --umbra-map-12: 0px 7px 8px -4px;\n    --umbra-map-13: 0px 7px 8px -4px;\n    --umbra-map-14: 0px 7px 9px -4px;\n    --umbra-map-15: 0px 8px 9px -5px;\n    --umbra-map-16: 0px 8px 10px -5px;\n    --umbra-map-17: 0px 8px 11px -5px;\n    --umbra-map-18: 0px 9px 11px -5px;\n    --umbra-map-19: 0px 9px 12px -6px;\n    --umbra-map-20: 0px 10px 13px -6px;\n    --umbra-map-21: 0px 10px 13px -6px;\n    --umbra-map-22: 0px 10px 14px -6px;\n    --umbra-map-23: 0px 11px 14px -7px;\n    --umbra-map-24: 0px 11px 15px -7px;\n    --penumbra-map-0: 0px 0px 0px 0px;\n    --penumbra-map-1: 0px 1px 1px 0px;\n    --penumbra-map-2: 0px 2px 2px 0px;\n    --penumbra-map-3: 0px 3px 4px 0px;\n    --penumbra-map-4: 0px 4px 5px 0px;\n    --penumbra-map-5: 0px 5px 8px 0px;\n    --penumbra-map-6: 0px 6px 10px 0px;\n    --penumbra-map-7: 0px 7px 10px 1px;\n    --penumbra-map-8: 0px 8px 10px 1px;\n    --penumbra-map-9: 0px 9px 12px 1px;\n    --penumbra-map-10: 0px 10px 14px 1px;\n    --penumbra-map-11: 0px 11px 15px 1px;\n    --penumbra-map-12: 0px 12px 17px 2px;\n    --penumbra-map-13: 0px 13px 19px 2px;\n    --penumbra-map-14: 0px 14px 21px 2px;\n    --penumbra-map-15: 0px 15px 22px 2px;\n    --penumbra-map-16: 0px 16px 24px 2px;\n    --penumbra-map-17: 0px 17px 26px 2px;\n    --penumbra-map-18: 0px 18px 28px 2px;\n    --penumbra-map-19: 0px 19px 29px 2px;\n    --penumbra-map-20: 0px 20px 31px 3px;\n    --penumbra-map-21: 0px 21px 33px 3px;\n    --penumbra-map-22: 0px 22px 35px 3px;\n    --penumbra-map-23: 0px 23px 36px 3px;\n    --penumbra-map-24: 0px 24px 38px 3px;\n    --ambient-map-0: 0px 0px 0px 0px;\n    --ambient-map-1: 0px 1px 3px 0px;\n    --ambient-map-2: 0px 1px 5px 0px;\n    --ambient-map-3: 0px 1px 8px 0px;\n    --ambient-map-4: 0px 1px 10px 0px;\n    --ambient-map-5: 0px 1px 14px 0px;\n    --ambient-map-6: 0px 1px 18px 0px;\n    --ambient-map-7: 0px 2px 16px 1px;\n    --ambient-map-8: 0px 3px 14px 2px;\n    --ambient-map-9: 0px 3px 16px 2px;\n    --ambient-map-10: 0px 4px 18px 3px;\n    --ambient-map-11: 0px 4px 20px 3px;\n    --ambient-map-12: 0px 5px 22px 4px;\n    --ambient-map-13: 0px 5px 24px 4px;\n    --ambient-map-14: 0px 5px 26px 4px;\n    --ambient-map-15: 0px 6px 28px 5px;\n    --ambient-map-16: 0px 6px 30px 5px;\n    --ambient-map-17: 0px 6px 32px 5px;\n    --ambient-map-18: 0px 7px 34px 6px;\n    --ambient-map-19: 0px 7px 36px 6px;\n    --ambient-map-20: 0px 8px 38px 7px;\n    --ambient-map-21: 0px 8px 40px 7px;\n    --ambient-map-22: 0px 8px 42px 7px;\n    --ambient-map-23: 0px 9px 44px 8px;\n    --ambient-map-24: 0px 9px 46px 8px;\n    --elevation-0:\n        var(--umbra-map-0) rgba(var(--baseline-color) var(--umbra-opacity)),\n        var(--penumbra-map-0) rgba(var(--baseline-color) var(--penumbra-opacity)),\n        var(--ambient-map-0) rgba(var(--baseline-color) var(--ambient-opacity))\n    ;\n    --elevation-1:\n        var(--umbra-map-1) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-1) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-1) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-2:\n        var(--umbra-map-2) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-2) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-2) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-3:\n        var(--umbra-map-3) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-3) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-3) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-4:\n        var(--umbra-map-4) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-4) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-4) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-5:\n        var(--umbra-map-5) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-5) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-5) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-6:\n        var(--umbra-map-6) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-6) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-6) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-7:\n        var(--umbra-map-7) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-7) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-7) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-8:\n        var(--umbra-map-8) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-8) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-8) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-9:\n        var(--umbra-map-9) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-9) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-9) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-10:\n        var(--umbra-map-10) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-10) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-10) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-11:\n        var(--umbra-map-11) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-11) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-11) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-12:\n        var(--umbra-map-12) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-12) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-12) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-13:\n        var(--umbra-map-13) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-13) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-13) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-14:\n        var(--umbra-map-14) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-14) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-14) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-15:\n        var(--umbra-map-15) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-15) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-15) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-16:\n        var(--umbra-map-16) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-16) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-16) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-17:\n        var(--umbra-map-17) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-17) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-17) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-18:\n        var(--umbra-map-18) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-18) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-18) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-19:\n        var(--umbra-map-19) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-19) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-19) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-20:\n        var(--umbra-map-20) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-20) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-20) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-21:\n        var(--umbra-map-21) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-21) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-21) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-22:\n        var(--umbra-map-22) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-22) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-22) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-23:\n        var(--umbra-map-23) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-23) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-23) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n    --elevation-24:\n        var(--umbra-map-24) rgba(var(--baseline-color), var(--umbra-opacity)),\n        var(--penumbra-map-24) rgba(var(--baseline-color), var(--penumbra-opacity)),\n        var(--ambient-map-24) rgba(var(--baseline-color), var(--ambient-opacity))\n    ;\n}\n.light-theme {\n    --default-surface: 255, 255, 255;\n    --default-background: 250, 250, 250;\n    --default-color: 50, 50, 50;\n}\n.dark-theme {\n    --default-surface: 24, 24, 24;\n    --default-background: 24, 24, 24;\n    --default-color: 255, 255, 255;\n    --overlay-color: 255, 255, 255;\n    --elevation-opacity-0: 0;\n    --elevation-opacity-1: 0.05;\n    --elevation-opacity-2: 0.07;\n    --elevation-opacity-3: 0.08;\n    --elevation-opacity-4: 0.09;\n    --elevation-opacity-5: 0.10;\n    --elevation-opacity-6: 0.11;\n    --elevation-opacity-7: 0.115;\n    --elevation-opacity-8: 0.12;\n    --elevation-opacity-9: 0.125;\n    --elevation-opacity-10: 0.13;\n    --elevation-opacity-11: 0.135;\n    --elevation-opacity-12: 0.14;\n    --elevation-opacity-13: 0.1425;\n    --elevation-opacity-14: 0.145;\n    --elevation-opacity-15: 0.1475;\n    --elevation-opacity-16: 0.15;\n    --elevation-opacity-17: 0.15125;\n    --elevation-opacity-18: 0.1525;\n    --elevation-opacity-19: 0.15375;\n    --elevation-opacity-20: 0.155;\n    --elevation-opacity-21: 0.15675;\n    --elevation-opacity-22: 0.1575;\n    --elevation-opacity-23: 0.15875;\n    --elevation-opacity-24: 0.16;\n}\n.elevation-background-0,\n.elevation-background-1,\n.elevation-background-2,\n.elevation-background-3,\n.elevation-background-4,\n.elevation-background-5,\n.elevation-background-6,\n.elevation-background-7,\n.elevation-background-8,\n.elevation-background-9,\n.elevation-background-10,\n.elevation-background-11,\n.elevation-background-12,\n.elevation-background-13,\n.elevation-background-14,\n.elevation-background-15,\n.elevation-background-16,\n.elevation-background-17,\n.elevation-background-18,\n.elevation-background-19,\n.elevation-background-20,\n.elevation-background-21,\n.elevation-background-22,\n.elevation-background-23,\n.elevation-background-24 {\n    position: relative;\n    background: rgb(var(--default-surface));\n}\n.elevation-background-0::before,\n.elevation-background-1::before,\n.elevation-background-2::before,\n.elevation-background-3::before,\n.elevation-background-4::before,\n.elevation-background-5::before,\n.elevation-background-6::before,\n.elevation-background-7::before,\n.elevation-background-8::before,\n.elevation-background-9::before,\n.elevation-background-10::before,\n.elevation-background-11::before,\n.elevation-background-12::before,\n.elevation-background-13::before,\n.elevation-background-14::before,\n.elevation-background-15::before,\n.elevation-background-16::before,\n.elevation-background-17::before,\n.elevation-background-18::before,\n.elevation-background-19::before,\n.elevation-background-20::before,\n.elevation-background-21::before,\n.elevation-background-22::before,\n.elevation-background-23::before,\n.elevation-background-24::before {\n    content: '';\n    position: absolute;\n    top: 0;\n    left: 0;\n    right: 0;\n    bottom: 0;\n    pointer-events: none;\n}\n.elevation-background-0::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-0));\n}\n.elevation-background-1::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-1));\n}\n.elevation-background-2::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-2));\n}\n.elevation-background-3::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-3));\n}\n.elevation-background-4::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-4));\n}\n.elevation-background-5::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-5));\n}\n.elevation-background-6::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-6));\n}\n.elevation-background-7::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-7));\n}\n.elevation-background-8::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-8));\n}\n.elevation-background-9::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-9));\n}\n.elevation-background-10::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-10));\n}\n.elevation-background-11::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-11));\n}\n.elevation-background-12::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-12));\n}\n.elevation-background-13::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-13));\n}\n.elevation-background-14::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-14));\n}\n.elevation-background-15::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-15));\n}\n.elevation-background-16::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-16));\n}\n.elevation-background-17::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-17));\n}\n.elevation-background-18::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-18));\n}\n.elevation-background-19::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-19));\n}\n.elevation-background-20::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-20));\n}\n.elevation-background-21::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-21));\n}\n.elevation-background-22::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-22));\n}\n.elevation-background-23::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-23));\n}\n.elevation-background-24::before {\n    background: rgba(var(--overlay-color), var(--elevation-opacity-24));\n}\n.elevation-0 {\n    box-shadow: var(--elevation-0);\n}\n.elevation-1 {\n    box-shadow: var(--elevation-1);\n}\n.elevation-2 {\n    box-shadow: var(--elevation-2);\n}\n.elevation-3 {\n    box-shadow: var(--elevation-3);\n}\n.elevation-4 {\n    box-shadow: var(--elevation-4);\n}\n.elevation-5 {\n    box-shadow: var(--elevation-5);\n}\n.elevation-6 {\n    box-shadow: var(--elevation-6);\n}\n.elevation-7 {\n    box-shadow: var(--elevation-7);\n}\n.elevation-8 {\n    box-shadow: var(--elevation-8);\n}\n.elevation-9 {\n    box-shadow: var(--elevation-9);\n}\n.elevation-10 {\n    box-shadow: var(--elevation-10);\n}\n.elevation-11 {\n    box-shadow: var(--elevation-11);\n}\n.elevation-12 {\n    box-shadow: var(--elevation-12);\n}\n.elevation-13 {\n    box-shadow: var(--elevation-13);\n}\n.elevation-14 {\n    box-shadow: var(--elevation-14);\n}\n.elevation-15 {\n    box-shadow: var(--elevation-15);\n}\n.elevation-16 {\n    box-shadow: var(--elevation-16);\n}\n.elevation-17 {\n    box-shadow: var(--elevation-17);\n}\n.elevation-18 {\n    box-shadow: var(--elevation-18);\n}\n.elevation-19 {\n    box-shadow: var(--elevation-19);\n}\n.elevation-20 {\n    box-shadow: var(--elevation-20);\n}\n.elevation-21 {\n    box-shadow: var(--elevation-21);\n}\n.elevation-22 {\n    box-shadow: var(--elevation-22);\n}\n.elevation-23 {\n    box-shadow: var(--elevation-23);\n}\n.elevation-24 {\n    box-shadow: var(--elevation-24);\n}\n";
styleInject(css_248z$k);

script$j.render = render$j;

/* eslint-disable import/prefer-default-export */

var components = /*#__PURE__*/Object.freeze({
    __proto__: null,
    VmActionButton: script$1,
    VmAppBar: script$2,
    VmButton: script$3,
    VmCard: script$4,
    VmCardContent: script$5,
    VmCardHeader: script$6,
    VmContainer: script$7,
    VmDatePicker: script$8,
    VmDivider: script$9,
    VmDropdown: script$a,
    VmGrid: script$c,
    VmLoadingSpinner: script$b,
    VmModal: script$d,
    VmNavigationDrawer: script$e,
    VmNumberField: script$f,
    VmPageHeader: script$g,
    VmSwitch: script$h,
    VmTextField: script$i,
    VmTheme: script$j
});

// Import vue components

const install = function installVue3Material(app) {
  Object.entries(components).forEach(([componentName, component]) => {
    app.component(componentName, component);
  });
}; // Create module definition for Vue.use()

export default install;
export { script$1 as VmActionButton, script$2 as VmAppBar, script$3 as VmButton, script$4 as VmCard, script$5 as VmCardContent, script$6 as VmCardHeader, script$7 as VmContainer, script$8 as VmDatePicker, script$9 as VmDivider, script$a as VmDropdown, script$c as VmGrid, script$b as VmLoadingSpinner, script$d as VmModal, script$e as VmNavigationDrawer, script$f as VmNumberField, script$g as VmPageHeader, script$h as VmSwitch, script$i as VmTextField, script$j as VmTheme };
