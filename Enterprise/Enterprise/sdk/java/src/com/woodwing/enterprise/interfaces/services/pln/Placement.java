/**
 * Placement.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pln;

public class Placement  implements java.io.Serializable {
    private double left;

    private double top;

    private java.lang.Integer columns;

    private java.lang.Double width;

    private java.lang.Double height;

    private java.lang.Boolean fixed;

    private java.lang.String layer;

    private java.lang.Double contentDx;

    private java.lang.Double contentDy;

    private java.lang.Double scaleX;

    private java.lang.Double scaleY;

    public Placement() {
    }

    public Placement(
           double left,
           double top,
           java.lang.Integer columns,
           java.lang.Double width,
           java.lang.Double height,
           java.lang.Boolean fixed,
           java.lang.String layer,
           java.lang.Double contentDx,
           java.lang.Double contentDy,
           java.lang.Double scaleX,
           java.lang.Double scaleY) {
           this.left = left;
           this.top = top;
           this.columns = columns;
           this.width = width;
           this.height = height;
           this.fixed = fixed;
           this.layer = layer;
           this.contentDx = contentDx;
           this.contentDy = contentDy;
           this.scaleX = scaleX;
           this.scaleY = scaleY;
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
     * Gets the columns value for this Placement.
     * 
     * @return columns
     */
    public java.lang.Integer getColumns() {
        return columns;
    }


    /**
     * Sets the columns value for this Placement.
     * 
     * @param columns
     */
    public void setColumns(java.lang.Integer columns) {
        this.columns = columns;
    }


    /**
     * Gets the width value for this Placement.
     * 
     * @return width
     */
    public java.lang.Double getWidth() {
        return width;
    }


    /**
     * Sets the width value for this Placement.
     * 
     * @param width
     */
    public void setWidth(java.lang.Double width) {
        this.width = width;
    }


    /**
     * Gets the height value for this Placement.
     * 
     * @return height
     */
    public java.lang.Double getHeight() {
        return height;
    }


    /**
     * Sets the height value for this Placement.
     * 
     * @param height
     */
    public void setHeight(java.lang.Double height) {
        this.height = height;
    }


    /**
     * Gets the fixed value for this Placement.
     * 
     * @return fixed
     */
    public java.lang.Boolean getFixed() {
        return fixed;
    }


    /**
     * Sets the fixed value for this Placement.
     * 
     * @param fixed
     */
    public void setFixed(java.lang.Boolean fixed) {
        this.fixed = fixed;
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
            this.left == other.getLeft() &&
            this.top == other.getTop() &&
            ((this.columns==null && other.getColumns()==null) || 
             (this.columns!=null &&
              this.columns.equals(other.getColumns()))) &&
            ((this.width==null && other.getWidth()==null) || 
             (this.width!=null &&
              this.width.equals(other.getWidth()))) &&
            ((this.height==null && other.getHeight()==null) || 
             (this.height!=null &&
              this.height.equals(other.getHeight()))) &&
            ((this.fixed==null && other.getFixed()==null) || 
             (this.fixed!=null &&
              this.fixed.equals(other.getFixed()))) &&
            ((this.layer==null && other.getLayer()==null) || 
             (this.layer!=null &&
              this.layer.equals(other.getLayer()))) &&
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
              this.scaleY.equals(other.getScaleY())));
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
        _hashCode += new Double(getLeft()).hashCode();
        _hashCode += new Double(getTop()).hashCode();
        if (getColumns() != null) {
            _hashCode += getColumns().hashCode();
        }
        if (getWidth() != null) {
            _hashCode += getWidth().hashCode();
        }
        if (getHeight() != null) {
            _hashCode += getHeight().hashCode();
        }
        if (getFixed() != null) {
            _hashCode += getFixed().hashCode();
        }
        if (getLayer() != null) {
            _hashCode += getLayer().hashCode();
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
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(Placement.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Placement"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
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
        elemField.setFieldName("columns");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Columns"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "int"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("width");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Width"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("height");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Height"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("fixed");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Fixed"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("layer");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Layer"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
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
