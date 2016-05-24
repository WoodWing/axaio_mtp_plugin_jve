/**
 * Placement.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class Placement  implements java.io.Serializable {
    private org.apache.axis.types.UnsignedInt page;

    private java.lang.String element;

    private java.lang.String elementID;

    private org.apache.axis.types.UnsignedInt frameOrder;

    private java.lang.String frameID;

    private double left;

    private double top;

    private double width;

    private double height;

    private java.lang.Double overset;

    private java.lang.Integer oversetChars;

    private java.lang.Integer oversetLines;

    private java.lang.String layer;

    private java.lang.String content;

    private com.woodwing.enterprise.interfaces.services.wfl.Edition edition;

    private java.lang.Double contentDx;

    private java.lang.Double contentDy;

    private java.lang.Double scaleX;

    private java.lang.Double scaleY;

    private org.apache.axis.types.UnsignedInt pageSequence;

    private java.lang.String pageNumber;

    private com.woodwing.enterprise.interfaces.services.wfl.PlacementTile[] tiles;

    private java.lang.String formWidgetId;

    private java.lang.String[] inDesignArticleIds;

    private com.woodwing.enterprise.interfaces.services.wfl.FrameType frameType;

    private java.lang.String splineID;

    public Placement() {
    }

    public Placement(
           org.apache.axis.types.UnsignedInt page,
           java.lang.String element,
           java.lang.String elementID,
           org.apache.axis.types.UnsignedInt frameOrder,
           java.lang.String frameID,
           double left,
           double top,
           double width,
           double height,
           java.lang.Double overset,
           java.lang.Integer oversetChars,
           java.lang.Integer oversetLines,
           java.lang.String layer,
           java.lang.String content,
           com.woodwing.enterprise.interfaces.services.wfl.Edition edition,
           java.lang.Double contentDx,
           java.lang.Double contentDy,
           java.lang.Double scaleX,
           java.lang.Double scaleY,
           org.apache.axis.types.UnsignedInt pageSequence,
           java.lang.String pageNumber,
           com.woodwing.enterprise.interfaces.services.wfl.PlacementTile[] tiles,
           java.lang.String formWidgetId,
           java.lang.String[] inDesignArticleIds,
           com.woodwing.enterprise.interfaces.services.wfl.FrameType frameType,
           java.lang.String splineID) {
           this.page = page;
           this.element = element;
           this.elementID = elementID;
           this.frameOrder = frameOrder;
           this.frameID = frameID;
           this.left = left;
           this.top = top;
           this.width = width;
           this.height = height;
           this.overset = overset;
           this.oversetChars = oversetChars;
           this.oversetLines = oversetLines;
           this.layer = layer;
           this.content = content;
           this.edition = edition;
           this.contentDx = contentDx;
           this.contentDy = contentDy;
           this.scaleX = scaleX;
           this.scaleY = scaleY;
           this.pageSequence = pageSequence;
           this.pageNumber = pageNumber;
           this.tiles = tiles;
           this.formWidgetId = formWidgetId;
           this.inDesignArticleIds = inDesignArticleIds;
           this.frameType = frameType;
           this.splineID = splineID;
    }


    /**
     * Gets the page value for this Placement.
     * 
     * @return page
     */
    public org.apache.axis.types.UnsignedInt getPage() {
        return page;
    }


    /**
     * Sets the page value for this Placement.
     * 
     * @param page
     */
    public void setPage(org.apache.axis.types.UnsignedInt page) {
        this.page = page;
    }


    /**
     * Gets the element value for this Placement.
     * 
     * @return element
     */
    public java.lang.String getElement() {
        return element;
    }


    /**
     * Sets the element value for this Placement.
     * 
     * @param element
     */
    public void setElement(java.lang.String element) {
        this.element = element;
    }


    /**
     * Gets the elementID value for this Placement.
     * 
     * @return elementID
     */
    public java.lang.String getElementID() {
        return elementID;
    }


    /**
     * Sets the elementID value for this Placement.
     * 
     * @param elementID
     */
    public void setElementID(java.lang.String elementID) {
        this.elementID = elementID;
    }


    /**
     * Gets the frameOrder value for this Placement.
     * 
     * @return frameOrder
     */
    public org.apache.axis.types.UnsignedInt getFrameOrder() {
        return frameOrder;
    }


    /**
     * Sets the frameOrder value for this Placement.
     * 
     * @param frameOrder
     */
    public void setFrameOrder(org.apache.axis.types.UnsignedInt frameOrder) {
        this.frameOrder = frameOrder;
    }


    /**
     * Gets the frameID value for this Placement.
     * 
     * @return frameID
     */
    public java.lang.String getFrameID() {
        return frameID;
    }


    /**
     * Sets the frameID value for this Placement.
     * 
     * @param frameID
     */
    public void setFrameID(java.lang.String frameID) {
        this.frameID = frameID;
    }


    /**
     * Gets the left value for this Placement.
     * 
     * @return left
     */
    public double getLeft() {
        return left;
    }


    /**
     * Sets the left value for this Placement.
     * 
     * @param left
     */
    public void setLeft(double left) {
        this.left = left;
    }


    /**
     * Gets the top value for this Placement.
     * 
     * @return top
     */
    public double getTop() {
        return top;
    }


    /**
     * Sets the top value for this Placement.
     * 
     * @param top
     */
    public void setTop(double top) {
        this.top = top;
    }


    /**
     * Gets the width value for this Placement.
     * 
     * @return width
     */
    public double getWidth() {
        return width;
    }


    /**
     * Sets the width value for this Placement.
     * 
     * @param width
     */
    public void setWidth(double width) {
        this.width = width;
    }


    /**
     * Gets the height value for this Placement.
     * 
     * @return height
     */
    public double getHeight() {
        return height;
    }


    /**
     * Sets the height value for this Placement.
     * 
     * @param height
     */
    public void setHeight(double height) {
        this.height = height;
    }


    /**
     * Gets the overset value for this Placement.
     * 
     * @return overset
     */
    public java.lang.Double getOverset() {
        return overset;
    }


    /**
     * Sets the overset value for this Placement.
     * 
     * @param overset
     */
    public void setOverset(java.lang.Double overset) {
        this.overset = overset;
    }


    /**
     * Gets the oversetChars value for this Placement.
     * 
     * @return oversetChars
     */
    public java.lang.Integer getOversetChars() {
        return oversetChars;
    }


    /**
     * Sets the oversetChars value for this Placement.
     * 
     * @param oversetChars
     */
    public void setOversetChars(java.lang.Integer oversetChars) {
        this.oversetChars = oversetChars;
    }


    /**
     * Gets the oversetLines value for this Placement.
     * 
     * @return oversetLines
     */
    public java.lang.Integer getOversetLines() {
        return oversetLines;
    }


    /**
     * Sets the oversetLines value for this Placement.
     * 
     * @param oversetLines
     */
    public void setOversetLines(java.lang.Integer oversetLines) {
        this.oversetLines = oversetLines;
    }


    /**
     * Gets the layer value for this Placement.
     * 
     * @return layer
     */
    public java.lang.String getLayer() {
        return layer;
    }


    /**
     * Sets the layer value for this Placement.
     * 
     * @param layer
     */
    public void setLayer(java.lang.String layer) {
        this.layer = layer;
    }


    /**
     * Gets the content value for this Placement.
     * 
     * @return content
     */
    public java.lang.String getContent() {
        return content;
    }


    /**
     * Sets the content value for this Placement.
     * 
     * @param content
     */
    public void setContent(java.lang.String content) {
        this.content = content;
    }


    /**
     * Gets the edition value for this Placement.
     * 
     * @return edition
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Edition getEdition() {
        return edition;
    }


    /**
     * Sets the edition value for this Placement.
     * 
     * @param edition
     */
    public void setEdition(com.woodwing.enterprise.interfaces.services.wfl.Edition edition) {
        this.edition = edition;
    }


    /**
     * Gets the contentDx value for this Placement.
     * 
     * @return contentDx
     */
    public java.lang.Double getContentDx() {
        return contentDx;
    }


    /**
     * Sets the contentDx value for this Placement.
     * 
     * @param contentDx
     */
    public void setContentDx(java.lang.Double contentDx) {
        this.contentDx = contentDx;
    }


    /**
     * Gets the contentDy value for this Placement.
     * 
     * @return contentDy
     */
    public java.lang.Double getContentDy() {
        return contentDy;
    }


    /**
     * Sets the contentDy value for this Placement.
     * 
     * @param contentDy
     */
    public void setContentDy(java.lang.Double contentDy) {
        this.contentDy = contentDy;
    }


    /**
     * Gets the scaleX value for this Placement.
     * 
     * @return scaleX
     */
    public java.lang.Double getScaleX() {
        return scaleX;
    }


    /**
     * Sets the scaleX value for this Placement.
     * 
     * @param scaleX
     */
    public void setScaleX(java.lang.Double scaleX) {
        this.scaleX = scaleX;
    }


    /**
     * Gets the scaleY value for this Placement.
     * 
     * @return scaleY
     */
    public java.lang.Double getScaleY() {
        return scaleY;
    }


    /**
     * Sets the scaleY value for this Placement.
     * 
     * @param scaleY
     */
    public void setScaleY(java.lang.Double scaleY) {
        this.scaleY = scaleY;
    }


    /**
     * Gets the pageSequence value for this Placement.
     * 
     * @return pageSequence
     */
    public org.apache.axis.types.UnsignedInt getPageSequence() {
        return pageSequence;
    }


    /**
     * Sets the pageSequence value for this Placement.
     * 
     * @param pageSequence
     */
    public void setPageSequence(org.apache.axis.types.UnsignedInt pageSequence) {
        this.pageSequence = pageSequence;
    }


    /**
     * Gets the pageNumber value for this Placement.
     * 
     * @return pageNumber
     */
    public java.lang.String getPageNumber() {
        return pageNumber;
    }


    /**
     * Sets the pageNumber value for this Placement.
     * 
     * @param pageNumber
     */
    public void setPageNumber(java.lang.String pageNumber) {
        this.pageNumber = pageNumber;
    }


    /**
     * Gets the tiles value for this Placement.
     * 
     * @return tiles
     */
    public com.woodwing.enterprise.interfaces.services.wfl.PlacementTile[] getTiles() {
        return tiles;
    }


    /**
     * Sets the tiles value for this Placement.
     * 
     * @param tiles
     */
    public void setTiles(com.woodwing.enterprise.interfaces.services.wfl.PlacementTile[] tiles) {
        this.tiles = tiles;
    }


    /**
     * Gets the formWidgetId value for this Placement.
     * 
     * @return formWidgetId
     */
    public java.lang.String getFormWidgetId() {
        return formWidgetId;
    }


    /**
     * Sets the formWidgetId value for this Placement.
     * 
     * @param formWidgetId
     */
    public void setFormWidgetId(java.lang.String formWidgetId) {
        this.formWidgetId = formWidgetId;
    }


    /**
     * Gets the inDesignArticleIds value for this Placement.
     * 
     * @return inDesignArticleIds
     */
    public java.lang.String[] getInDesignArticleIds() {
        return inDesignArticleIds;
    }


    /**
     * Sets the inDesignArticleIds value for this Placement.
     * 
     * @param inDesignArticleIds
     */
    public void setInDesignArticleIds(java.lang.String[] inDesignArticleIds) {
        this.inDesignArticleIds = inDesignArticleIds;
    }


    /**
     * Gets the frameType value for this Placement.
     * 
     * @return frameType
     */
    public com.woodwing.enterprise.interfaces.services.wfl.FrameType getFrameType() {
        return frameType;
    }


    /**
     * Sets the frameType value for this Placement.
     * 
     * @param frameType
     */
    public void setFrameType(com.woodwing.enterprise.interfaces.services.wfl.FrameType frameType) {
        this.frameType = frameType;
    }


    /**
     * Gets the splineID value for this Placement.
     * 
     * @return splineID
     */
    public java.lang.String getSplineID() {
        return splineID;
    }


    /**
     * Sets the splineID value for this Placement.
     * 
     * @param splineID
     */
    public void setSplineID(java.lang.String splineID) {
        this.splineID = splineID;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof Placement)) return false;
        Placement other = (Placement) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.page==null && other.getPage()==null) || 
             (this.page!=null &&
              this.page.equals(other.getPage()))) &&
            ((this.element==null && other.getElement()==null) || 
             (this.element!=null &&
              this.element.equals(other.getElement()))) &&
            ((this.elementID==null && other.getElementID()==null) || 
             (this.elementID!=null &&
              this.elementID.equals(other.getElementID()))) &&
            ((this.frameOrder==null && other.getFrameOrder()==null) || 
             (this.frameOrder!=null &&
              this.frameOrder.equals(other.getFrameOrder()))) &&
            ((this.frameID==null && other.getFrameID()==null) || 
             (this.frameID!=null &&
              this.frameID.equals(other.getFrameID()))) &&
            this.left == other.getLeft() &&
            this.top == other.getTop() &&
            this.width == other.getWidth() &&
            this.height == other.getHeight() &&
            ((this.overset==null && other.getOverset()==null) || 
             (this.overset!=null &&
              this.overset.equals(other.getOverset()))) &&
            ((this.oversetChars==null && other.getOversetChars()==null) || 
             (this.oversetChars!=null &&
              this.oversetChars.equals(other.getOversetChars()))) &&
            ((this.oversetLines==null && other.getOversetLines()==null) || 
             (this.oversetLines!=null &&
              this.oversetLines.equals(other.getOversetLines()))) &&
            ((this.layer==null && other.getLayer()==null) || 
             (this.layer!=null &&
              this.layer.equals(other.getLayer()))) &&
            ((this.content==null && other.getContent()==null) || 
             (this.content!=null &&
              this.content.equals(other.getContent()))) &&
            ((this.edition==null && other.getEdition()==null) || 
             (this.edition!=null &&
              this.edition.equals(other.getEdition()))) &&
            ((this.contentDx==null && other.getContentDx()==null) || 
             (this.contentDx!=null &&
              this.contentDx.equals(other.getContentDx()))) &&
            ((this.contentDy==null && other.getContentDy()==null) || 
             (this.contentDy!=null &&
              this.contentDy.equals(other.getContentDy()))) &&
            ((this.scaleX==null && other.getScaleX()==null) || 
             (this.scaleX!=null &&
              this.scaleX.equals(other.getScaleX()))) &&
            ((this.scaleY==null && other.getScaleY()==null) || 
             (this.scaleY!=null &&
              this.scaleY.equals(other.getScaleY()))) &&
            ((this.pageSequence==null && other.getPageSequence()==null) || 
             (this.pageSequence!=null &&
              this.pageSequence.equals(other.getPageSequence()))) &&
            ((this.pageNumber==null && other.getPageNumber()==null) || 
             (this.pageNumber!=null &&
              this.pageNumber.equals(other.getPageNumber()))) &&
            ((this.tiles==null && other.getTiles()==null) || 
             (this.tiles!=null &&
              java.util.Arrays.equals(this.tiles, other.getTiles()))) &&
            ((this.formWidgetId==null && other.getFormWidgetId()==null) || 
             (this.formWidgetId!=null &&
              this.formWidgetId.equals(other.getFormWidgetId()))) &&
            ((this.inDesignArticleIds==null && other.getInDesignArticleIds()==null) || 
             (this.inDesignArticleIds!=null &&
              java.util.Arrays.equals(this.inDesignArticleIds, other.getInDesignArticleIds()))) &&
            ((this.frameType==null && other.getFrameType()==null) || 
             (this.frameType!=null &&
              this.frameType.equals(other.getFrameType()))) &&
            ((this.splineID==null && other.getSplineID()==null) || 
             (this.splineID!=null &&
              this.splineID.equals(other.getSplineID())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getPage() != null) {
            _hashCode += getPage().hashCode();
        }
        if (getElement() != null) {
            _hashCode += getElement().hashCode();
        }
        if (getElementID() != null) {
            _hashCode += getElementID().hashCode();
        }
        if (getFrameOrder() != null) {
            _hashCode += getFrameOrder().hashCode();
        }
        if (getFrameID() != null) {
            _hashCode += getFrameID().hashCode();
        }
        _hashCode += new Double(getLeft()).hashCode();
        _hashCode += new Double(getTop()).hashCode();
        _hashCode += new Double(getWidth()).hashCode();
        _hashCode += new Double(getHeight()).hashCode();
        if (getOverset() != null) {
            _hashCode += getOverset().hashCode();
        }
        if (getOversetChars() != null) {
            _hashCode += getOversetChars().hashCode();
        }
        if (getOversetLines() != null) {
            _hashCode += getOversetLines().hashCode();
        }
        if (getLayer() != null) {
            _hashCode += getLayer().hashCode();
        }
        if (getContent() != null) {
            _hashCode += getContent().hashCode();
        }
        if (getEdition() != null) {
            _hashCode += getEdition().hashCode();
        }
        if (getContentDx() != null) {
            _hashCode += getContentDx().hashCode();
        }
        if (getContentDy() != null) {
            _hashCode += getContentDy().hashCode();
        }
        if (getScaleX() != null) {
            _hashCode += getScaleX().hashCode();
        }
        if (getScaleY() != null) {
            _hashCode += getScaleY().hashCode();
        }
        if (getPageSequence() != null) {
            _hashCode += getPageSequence().hashCode();
        }
        if (getPageNumber() != null) {
            _hashCode += getPageNumber().hashCode();
        }
        if (getTiles() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getTiles());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getTiles(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getFormWidgetId() != null) {
            _hashCode += getFormWidgetId().hashCode();
        }
        if (getInDesignArticleIds() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getInDesignArticleIds());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getInDesignArticleIds(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getFrameType() != null) {
            _hashCode += getFrameType().hashCode();
        }
        if (getSplineID() != null) {
            _hashCode += getSplineID().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(Placement.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Placement"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("page");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Page"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("element");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Element"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("elementID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ElementID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("frameOrder");
        elemField.setXmlName(new javax.xml.namespace.QName("", "FrameOrder"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("frameID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "FrameID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("left");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Left"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("top");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Top"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("width");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Width"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("height");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Height"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("overset");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Overset"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("oversetChars");
        elemField.setXmlName(new javax.xml.namespace.QName("", "OversetChars"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "int"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("oversetLines");
        elemField.setXmlName(new javax.xml.namespace.QName("", "OversetLines"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "int"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("layer");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Layer"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("content");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Content"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("edition");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Edition"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Edition"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("contentDx");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ContentDx"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("contentDy");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ContentDy"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("scaleX");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ScaleX"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("scaleY");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ScaleY"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pageSequence");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PageSequence"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pageNumber");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PageNumber"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("tiles");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Tiles"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "PlacementTile"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "PlacementTile"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("formWidgetId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "FormWidgetId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("inDesignArticleIds");
        elemField.setXmlName(new javax.xml.namespace.QName("", "InDesignArticleIds"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("frameType");
        elemField.setXmlName(new javax.xml.namespace.QName("", "FrameType"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "FrameType"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("splineID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SplineID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
