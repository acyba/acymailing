const acym_helperFlow = {
    pathNumber: 1,
    allLinks: [],
    allLinksElements: {},
    endLinksConditionByFrom: {},
    cardsCountLastRow: 0,
    currentTransformValue: '',
    currentWidth: 0,
    CARD_WIDTH_FOR_CONTAINER: 500,
    ZOOM_STEP: 0.1,
    strokeWidth: '1px',
    strokeColor: '#A6A6A6',
    strokeDasharray: '3',
    slugLength: 24,
    startTranslateX: 0,
    undoFunction: null,
    redoFunction: null,
    listOfExistingSlugs: [],
    createFlow: function (flow, params) {
        const elementToAddFlow = document.getElementById(params.id);

        // If the flow is already displayed, we remove it
        if (!!elementToAddFlow.querySelector('#flow__svg__container')) {
            elementToAddFlow.innerHTML = '';
        }

        elementToAddFlow.style.position = 'relative';

        // Reset pathNumber and allLinks globals to allow on-click function to re-call createFlow()
        this.pathNumber = 1;
        this.allLinks = [];
        this.cardsCountLastRow = 0;
        this.allLinksElements = {};

        elementToAddFlow.innerHTML = '<div id="flow__container"></div>';
        elementToAddFlow.innerHTML += this.getToolboxHtml();
        const flowContainer = document.getElementById('flow__container');
        this.enableToolboxActions(flowContainer);

        // Add SVG container
        flowContainer.innerHTML += '<div id="flow__svg__container"><svg id="flow__svg__container__svg"></svg></div>';
        const svgContainer = document.getElementById('flow__svg__container');

        // Add HTML container
        flowContainer.innerHTML += `<div id="flow__tree__container">${this.createCard(flow[0])}</div>`;
        const treeContainer = document.getElementById('flow__tree__container');

        if (flow[0].children === undefined) {
            this.createLink(document.getElementById('flow__svg__container__svg'), flow[0].slug);
            this.cardsCountLastRow++;
        } else {
            this.iterate(flow[0].children, true, flow[0].slug);
        }

        document.getElementById('flow__tree__container').style.width = `${this.cardsCountLastRow * this.CARD_WIDTH_FOR_CONTAINER}px`;

        this.connectCard();

        // Set the width and height of the flow container
        flowContainer.style.width = treeContainer.offsetWidth + 'px';
        flowContainer.style.height = treeContainer.offsetHeight + 'px';
        flowContainer.style.transformOrigin = 'top left';

        // Center the flow container
        const translateValues = this.decryptTransform(this.currentTransformValue);
        // If the container didn't change size we keep the same translateX
        if (this.currentWidth !== treeContainer.offsetWidth) {
            this.startTranslateX = elementToAddFlow.offsetWidth / 2 - treeContainer.offsetWidth / 2;
        } else {
            this.startTranslateX = translateValues.x;
        }
        this.currentWidth = treeContainer.offsetWidth;

        if (!this.currentTransformValue) {
            flowContainer.style.transform = `translate(${this.startTranslateX}px, 0) scale(1)`;
            this.currentTransformValue = `translate(${this.startTranslateX}px, 0) scale(1)`;
        } else {
            this.editTransform('translate', `${this.startTranslateX}px, ${translateValues.y}px`, flowContainer);
            flowContainer.style.transform = this.currentTransformValue;
        }

        this.dragFlow(elementToAddFlow, flowContainer);
        this.scrollFlow(elementToAddFlow, flowContainer);
        this.hoverAddButton();

        const allCards = document.querySelectorAll('.flow__step__card');
        allCards.forEach((card) => {
            card.addEventListener('click', function (event) {
                if (!event.target.classList.contains('flow__step__card__add') && typeof params.cardClick === 'function') {
                    params.cardClick(card.closest('.flow__step__card__container').getAttribute('id'));
                }
            });
        });

        const allAddButtons = document.querySelectorAll('.flow__step__card__add');
        allAddButtons.forEach((addButton) => {
            addButton.addEventListener('click', function () {
                if (typeof params.addButtonClick === 'function') {
                    params.addButtonClick(addButton.closest('.flow__step__card__container').getAttribute('id'));
                }
            });
        });

        if (typeof params.undoFunction === 'function') {
            this.undoFunction = params.undoFunction;
        }

        if (typeof params.redoFunction === 'function') {
            this.redoFunction = params.redoFunction;
        }

        if (params.listOfExistingSlugs && params.listOfExistingSlugs.length) {
            this.listOfExistingSlugs = params.listOfExistingSlugs;
        }

        window.onresize = () => {
            svgContainer.setAttribute('height', '0');
            svgContainer.setAttribute('width', '0');
            this.connectCard();
        };
    },
    connectCard: function () {
        const svg = document.getElementById('flow__svg__container__svg');
        for (let i = 0 ; this.allLinks.length > i ; i++) {
            if (this.allLinks[i].end !== undefined) {
                this.connectElements(
                    svg,
                    document.getElementById(this.allLinks[i].pathId),
                    document.getElementById(this.allLinks[i].from),
                    document.getElementById(this.allLinks[i].end)
                );
            } else {
                this.endElements(svg, document.getElementById(this.allLinks[i].pathId), document.getElementById(this.allLinks[i].from));
            }
        }
    },
    iterate: function (flow, start, from) {
        const svgContainer = document.getElementById('flow__svg__container__svg');
        const treeContainer = document.createElement('div');
        treeContainer.classList.add('flow__container__branch', `from_${from}`);
        document.getElementById(from).after(treeContainer);

        let tempFrom = from;

        for (const key in flow) {
            const currentNode = flow[key];

            const isEnd = currentNode.children === undefined || currentNode.children.length === 0;

            if (!document.getElementById(`card_${currentNode.slug}`)) {
                treeContainer.innerHTML += this.createCard(currentNode, currentNode.conditionEnd ? currentNode.conditionEnd : false, isEnd);
            }

            if ((from && !start) || start) {
                this.createLink(svgContainer, from, currentNode.slug);
                tempFrom = currentNode.slug;
            }

            if (currentNode.children !== undefined && currentNode.children.length > 0) {
                this.iterate(currentNode.children, false, currentNode.slug);
            } else {
                if (currentNode.conditionEnd !== true) {
                    this.createLink(svgContainer, tempFrom);
                }

                this.cardsCountLastRow++;
                tempFrom = from;
            }
        }
    },
    connectElements: function (svg, path, startElement, endElement) {
        const svgContainer = document.getElementById('flow__svg__container');

        // If first element is lower than the second we swap them
        if (startElement.offsetTop > endElement.offsetTop) {
            const temp = startElement;
            startElement = endElement;
            endElement = temp;
        }

        // Get (top, left) corner coordinates of the svg container
        const svgTop = svgContainer.offsetTop;
        const svgLeft = svgContainer.offsetLeft;

        // Calculate path's start (x,y)  coords
        // We want the x coordinate to visually result in the element's mid-point
        const startX = startElement.offsetLeft + 0.5 * startElement.offsetWidth - svgLeft;    // x = left offset + 0.5*width - svg's left offset
        const startY = startElement.offsetTop + startElement.offsetHeight - svgTop;        // y = top offset + height - svg's top offset

        // Calculate path's end (x,y) coords
        const endX = endElement.offsetLeft + 0.5 * endElement.offsetWidth - svgLeft;
        const endY = endElement.offsetTop - svgTop;

        // Call function for drawing the path
        this.drawPath(svg, path, startX, startY, endX, endY);
    },
    endElements: function (svg, path, startElement) {
        const svgContainer = document.getElementById('flow__svg__container');

        // Get (top, left) corner coordinates of the svg container
        const svgTop = svgContainer.offsetTop;
        const svgLeft = svgContainer.offsetLeft;

        // Calculate path's start (x,y)  coords
        // We want the x coordinate to visually result in the element's mid-point
        const startX = startElement.offsetLeft + 0.5 * startElement.offsetWidth - svgLeft;    // x = left offset + 0.5*width - svg's left offset
        const startY = startElement.offsetTop + startElement.offsetHeight - svgTop;        // y = top offset + height - svg's top offset

        // Calculate path's end (x,y) coords
        const endX = startX;
        const endY = startY + 60;

        // Call function for drawing the path
        this.drawPath(svg, path, startX, startY, endX, endY);
    },
    drawPath: function (svg, path, startX, startY, endX, endY) {
        // get the path's stroke width (if one wanted to be  really precise, one could use half the stroke size)
        const stroke = parseFloat(path.getAttribute('stroke-width'));
        // check if the svg is big enough to draw the path, if not, set high/width
        if (svg.getAttribute('height') < endY) svg.setAttribute('height', endY);
        if (svg.getAttribute('width') < (startX + stroke)) svg.setAttribute('width', (startX + stroke));
        if (svg.getAttribute('width') < (endX + stroke)) svg.setAttribute('width', (endX + stroke));

        const deltaRadiusX = (endX - startX) * 0.15;
        const deltaRadiusY = (endY - startY) * 0.15;
        const deltaHalfX = (endX - startX) * 0.5;
        const deltaHalfY = (endY - startY) * 0.5;
        // for further calculations which ever is the shortest distance
        const radius = deltaRadiusY < this.absolute(deltaRadiusX) ? deltaRadiusY : this.absolute(deltaRadiusX);
        const deltaHalf = deltaHalfY < this.absolute(deltaHalfX) ? deltaHalfY : this.absolute(deltaHalfX);

        // set sweep-flag (counter/clock-wise)
        // if start element is closer to the left edge,
        // draw the first arc counter-clockwise, and the second one clock-wise
        let arc = 1;
        if (startX > endX) {
            arc = 0;
        }

        // draw tha pipe-like path
        // Letters: Parameters Explanation
        // M: X Y move cursor to X Y but don't draw
        // V: Y vertical line to Y
        // H: X horizontal line to X
        // A: radiusX radiusY x-axis-rotation large-arc-flag sweep-flag x y Draw an arc from the current point to the point (x, y last 2 parameters)
        const value = `M ${startX} ${startY} 
        V ${startY + deltaHalf} 
        H ${endX - radius * this.signum(deltaRadiusX)} 
        A ${radius} ${radius} 0 0 ${arc} ${endX} ${startY + deltaHalf + radius} 
        V ${endY}`;
        path.setAttribute('d', value);
    },
    signum: function (x) {
        return (x < 0) ? -1 : 1;
    },
    absolute: function (x) {
        return (x < 0) ? -x : x;
    },
    createCard: function (currentFlow, isConditionEnd = false, isEnd = false) {
        const cardAddClass = isConditionEnd ? 'flow__step__card__add--condition' : 'flow__step__card__add--straight';
        const cardEndClass = isEnd ? 'flow__step__container__end' : '';
        // TODO trad
        const addButton = currentFlow.condition
                          ? '<span class="flow__step__card__condition__yes">Yes</span><span class="flow__step__card__condition__no">No</span>'
                          : `<span class="flow__step__card__add ${cardAddClass}">+</span>`;
        return `<div class="flow__step__container ${cardEndClass}">
            <div class="flow__step__card__container ${isConditionEnd ? 'flow__step__card__container__condition__end' : ''}" id="${currentFlow.slug}">
                <div id="card_${currentFlow.slug}" class="flow__step__card ${isConditionEnd ? 'flow__step__card__container__end' : ''}">${currentFlow.html}</div>
                ${addButton}
            </div>
        </div>`;
    },
    createLink: function (svgContainer, from, end = undefined) {
        const pathId = `path${this.pathNumber}`;
        const newPath = `<path data-flow-from="${from}" id="${pathId}" stroke="${this.strokeColor}" fill="none" stroke-width="${this.strokeWidth}" stroke-dasharray="${this.strokeDasharray}"></path>`;
        this.allLinks.push({
            pathId,
            from,
            end
        });
        this.pathNumber++;
        svgContainer.innerHTML += newPath;

        if (!this.allLinksElements[from]) {
            this.allLinksElements[from] = [];
        }

        this.allLinksElements[from].push(document.getElementById(pathId));

        return pathId;
    },
    decryptTransform: function (transform) {
        if (!transform) {
            return {
                x: 0,
                y: 0
            };
        }

        const translateValues = transform.match(/translate\((.+?)\)/)[1].split(',');

        return {
            x: parseInt(translateValues[0]),
            y: parseInt(translateValues[1])
        };
    },
    dragFlow: function (elementToAddFlow, flowContainer) {
        // Move the flow container
        const mouseMoveEvent = (event) => {
            const currentTransform = this.decryptTransform(flowContainer.style.transform);
            const translateValue = `${currentTransform.x + event.movementX}px, ${currentTransform.y + event.movementY}px`;
            acym_helperFlow.editTransform('translate', translateValue, flowContainer);
        };

        // Get the current translate values

        // Listen to the drag event
        elementToAddFlow.addEventListener('mousedown', function (event) {
            if (event.target.classList.contains('flow__step__card__add')) {
                return;
            }

            // If right click, we don't want to drag
            if (event.button === 2) {
                return;
            }

            document.body.style.cursor = 'grabbing';
            window.addEventListener('mousemove', mouseMoveEvent);
            window.addEventListener('mouseup', function () {
                // Reset the cursor to default
                document.body.style.cursor = 'default';
                window.removeEventListener('mousemove', mouseMoveEvent);
            });
        });
    },
    scrollFlow: function (elementToAddFlow, flowContainer) {
        elementToAddFlow.addEventListener('wheel', (event) => {
            event.preventDefault();

            if (event.deltaY === 0) {
                return;
            }

            const scale = parseFloat(flowContainer.style.transform.match(/scale\((.+?)\)/)[1]);

            // Make sure that the scroll is smooth and not too fast
            const newScale = scale + event.deltaY * -0.002;
            const newVerifiedScale = Math.min(Math.max(0.5, newScale), 2);

            if (newScale !== newVerifiedScale) {
                return;
            }

            const currentTransform = this.decryptTransform(flowContainer.style.transform);

            const pointer = {
                x: event.clientX - elementToAddFlow.getBoundingClientRect().left,
                y: event.clientY - elementToAddFlow.getBoundingClientRect().top
            };
            const target = {
                x: (pointer.x - currentTransform.x) / scale,
                y: (pointer.y - currentTransform.y) / scale
            };
            const newTransform = {
                x: pointer.x - target.x * newVerifiedScale,
                y: pointer.y - target.y * newVerifiedScale
            };

            acym_helperFlow.editTransform('scale', newVerifiedScale, flowContainer);

            const translateValue = `${newTransform.x}px, ${newTransform.y}px`;
            acym_helperFlow.editTransform('translate', translateValue, flowContainer);
        });
    },
    editTransform: function (type, value, flowContainer) {
        const regex = new RegExp(`${type}\\((.+?)\\)`);
        const newValue = this.currentTransformValue.replace(regex, `${type}(${value})`);
        flowContainer.style.transform = newValue;
        this.currentTransformValue = newValue;
    },
    isSlugExist: function (slug) {
        const allSlugs = Object.keys(this.allLinksElements);
        return allSlugs.includes(slug);
    },
    updateNodeInFlow: function (currentFlow, node) {
        for (const key in currentFlow) {
            if (currentFlow[key].slug === node.slug) {
                currentFlow[key].html = node.html;
                currentFlow[key].params = node.params;
                break;
            }

            if (currentFlow[key].children !== undefined) {
                this.updateNodeInFlow(currentFlow[key].children, node);
            }
        }
    },
    deleteNode: function (currentFlow, slug) {
        for (const key in currentFlow) {
            if (currentFlow[key].slug === slug) {
                currentFlow.splice(key, 1);
                return;
            }

            if (currentFlow[key].children !== undefined) {
                this.deleteNode(currentFlow[key].children, slug);
                if (currentFlow[key].children.length === 0) {
                    delete currentFlow[key].children;
                }
            }
        }
    },
    addUpdateNewNode: function (currentFlow, newNode, parentSlug = null) {
        const isUpdating = this.isSlugExist(newNode.slug);

        if (isUpdating) {
            this.updateNodeInFlow(currentFlow, newNode);
            return;
        }

        if (parentSlug === null) {
            currentFlow.push(newNode);
        } else {
            // Search for the parent node
            for (const key in currentFlow) {
                if (currentFlow[key].slug === parentSlug) {
                    if (currentFlow[key].children === undefined) {
                        currentFlow[key].children = [];
                    }

                    // If we add in the middle of the flow
                    if (currentFlow[key].children.length) {
                        if (newNode.condition) {
                            newNode.children[0].children = [...currentFlow[key].children];
                        } else {
                            newNode.children = [...currentFlow[key].children];
                        }
                    }

                    currentFlow[key].children = [newNode];

                    break;
                }

                if (currentFlow[key].children !== undefined) {
                    this.addUpdateNewNode(currentFlow[key].children, newNode, parentSlug);
                }
            }
        }
    },
    createNode: function (label, icon, isCondition = false) {
        const node = {
            html: `<div class="acym__flow__card__content"><i class="acymicon-${icon}"></i><p>${label}</p></div>`,
            slug: this.generateRandomString(this.slugLength),
            condition: isCondition
        };

        if (isCondition) {
            node.children = [
                {
                    html: '',
                    slug: this.generateRandomString(this.slugLength),
                    conditionEnd: true,
                    conditionValid: true
                },
                {
                    html: '',
                    slug: this.generateRandomString(this.slugLength),
                    conditionEnd: true,
                    conditionValid: false
                }
            ];
        }

        return node;
    },
    generateRandomString: function (length) {
        const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        let randomString = '';

        for (let i = 0 ; i < length ; i++) {
            const randomIndex = Math.floor(Math.random() * letters.length);
            randomString += letters[randomIndex];
        }

        if (this.listOfExistingSlugs.includes(randomString)) {
            return this.generateRandomString(length);
        }

        return randomString;
    },
    hoverAddButton: function () {
        const addButtons = document.querySelectorAll('.flow__step__card__add');
        addButtons.forEach((addButton) => {
            addButton.addEventListener('mouseenter', (event) => {
                const idCard = addButton.closest('.flow__step__card__container').getAttribute('id');

                if (!this.allLinksElements[idCard] || !this.allLinksElements[idCard].length) {
                    return;
                }
                const path = this.allLinksElements[idCard][0];
                const pathBaseColor = path.style.stroke;

                // TODO color variable
                path.style.stroke = '#00A4FF';
                addButton.addEventListener('mouseleave', () => {
                    path.style.stroke = pathBaseColor;
                });
            });
        });
    },
    getToolboxHtml: function () {
        return `<div id="flow__toolbox">
<div class="flow__toolbox__item" id="flow__toolbox__item__center"><i class="acymicon-checkbox-empty"></i></div>
<div class="flow__toolbox__item" id="flow__toolbox__item__zoom-in"><i class="acymicon-add"></i></div>
<div class="flow__toolbox__item" id="flow__toolbox__item__zoom-out"><i class="acymicon-minus"></i></div>
<div class="flow__toolbox__item" id="flow__toolbox__item__undo"><i class="acymicon-rotate-left"></i></div>
<div class="flow__toolbox__item" id="flow__toolbox__item__redo"><i class="acymicon-repeat"></i></div>
</div>`;
    },
    enableToolboxActions: function (flowContainer) {
        this.toolboxZoomAction(flowContainer);
        this.toolboxCenterAction(flowContainer);
        this.setUndo();
        this.setRedo();
    },
    toolboxCenterAction: function (flowContainer) {
        document.getElementById('flow__toolbox__item__center').addEventListener('click', () => {
            flowContainer.style.transform = `translate(${this.startTranslateX}px, 0) scale(1)`;
            this.currentTransformValue = `translate(${this.startTranslateX}px, 0) scale(1)`;
        });
    },
    toolboxZoomAction: function (flowContainer) {
        document.getElementById('flow__toolbox__item__zoom-in').addEventListener('click', () => {
            const scale = parseFloat(flowContainer.style.transform.match(/scale\((.+?)\)/)[1]);
            let newScale = scale + this.ZOOM_STEP;
            newScale = Math.min(Math.max(0.5, newScale), 2);
            this.editTransform('scale', newScale, flowContainer);
        });
        document.getElementById('flow__toolbox__item__zoom-out').addEventListener('click', () => {
            const scale = parseFloat(flowContainer.style.transform.match(/scale\((.+?)\)/)[1]);
            let newScale = scale - this.ZOOM_STEP;
            newScale = Math.min(Math.max(0.5, newScale), 2);
            this.editTransform('scale', newScale, flowContainer);
        });
    },
    setUndo: function () {
        document.getElementById('flow__toolbox__item__undo').addEventListener('click', () => {
            this.undoFunction();
        });
    },
    setRedo: function () {
        document.getElementById('flow__toolbox__item__redo').addEventListener('click', () => {
            this.redoFunction();
        });
    }
};

